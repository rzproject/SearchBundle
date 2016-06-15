<?php
namespace Rz\SearchBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Sensio\Bundle\GeneratorBundle\Command\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Question\Question;

class SolrIndexCommand extends ContainerAwareCommand
{
    protected $configManager;
    protected $configKey;

    /**
     * @return mixed
     */
    public function getConfigManager()
    {
        return $this->configManager;
    }

    /**
     * @param mixed $configManager
     */
    public function setConfigManager($configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * @return mixed
     */
    public function getConfigKey()
    {
        return $this->configKey;
    }

    /**
     * @param mixed $configKey
     */
    public function setConfigKey($configKey)
    {
        $this->configKey = $configKey;
    }

    protected function configure() {

        $this->setName('rz:solr:index')
            ->setDescription('Index doctrine entity using Apache Solr.')
            ->setDefinition(
                new InputDefinition(array(
                    new InputArgument('entity', InputArgument::REQUIRED),
                ))
            );
    }

    /**
     * Initializes the command just after the input has been validated.
     *
     * This is mainly useful when a lot of commands extends one main command
     * where some things need to be initialized based on the input arguments and options.
     *
     * @param InputInterface  $input  An InputInterface instance
     * @param OutputInterface $output An OutputInterface instance
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $configManager = $this->getContainer()->get('rz_search.manager.config');
        $this->setConfigManager($configManager);
        $entity = $input->getArgument('entity');

        if ($entity) {
            $this->setConfigKey(preg_replace('/\//', '.', strtolower($entity)));
            $options = $this->getCLIOptions($entity);
            foreach($options as $option) {
                $opt = (strtoupper($option['is_required']) == 'REQUIRED') ? InputOption::VALUE_REQUIRED : InputOption::VALUE_OPTIONAL;
                $this->getDefinition()->addOption(new InputOption($option['name'], null, $opt | InputOption::VALUE_IS_ARRAY, $option['description']));
            }
        }
    }

    /**
     * Interacts with the user.
     *
     * This method is executed before the InputDefinition is validated.
     * This means that this is the only place where the command can
     * interactively ask for values of missing required arguments.
     *
     * @param InputInterface  $input  An InputInterface instance
     * @param OutputInterface $output An OutputInterface instance
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $entity =  $this->getConfigKey() ?: preg_replace('/\//', '.', strtolower($input->getArgument('entity')));
        if ($entity) {
            $options = $this->getCLIOptions($entity);
            foreach($options as $option) {
                $ans = $this->promptOption(
                    $input,
                    $output,
                    $option['description'],
                    $input->getOption($option['name'])
                );
                $input->setOption($option['name'], $ans);
            }
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $opt = [];
        $options = $this->getCLIOptions($this->configKey);
        foreach($options as $option) {
            $opt[$option['name']] = $input->getOption($option['name']);
        }

        $info_style = new OutputFormatterStyle('white', null, array('bold'));
        $output->getFormatter()->setStyle('rz-msg', $info_style);

        $error_style = new OutputFormatterStyle('red', null, array('bold'));
        $output->getFormatter()->setStyle('rz-err', $error_style);

        $msg_progress_style = new OutputFormatterStyle('yellow', null, array('bold'));
        $output->getFormatter()->setStyle('rz-msg-progress', $msg_progress_style);

        $msg_progress_style2 = new OutputFormatterStyle('cyan', null, array('bold'));
        $output->getFormatter()->setStyle('rz-msg-progress2', $msg_progress_style2);

        $clientName = sprintf('solarium.client.%s', $this->configKey);
        $searchClient = $this->getContainer()->has($clientName) ? $this->getContainer()->get($clientName) : null;

        $indexManager = $this->getContainer()->get('rz_search.manager.solr.index');

        $modelProcessorService = $this->getConfigManager()->getModelProcessor($this->configKey) ?: null;
        $modelProcessorService = ($modelProcessorService && $this->getContainer()->has($modelProcessorService)) ? $this->getContainer()->get($modelProcessorService) : null;


        if ($this->configKey && $indexManager && $modelProcessorService) {
            $output->writeln(sprintf('<info>Indexing entity: <rz-msg>%s</rz-msg></info>', $this->configKey));

            $data = $modelProcessorService->fetchAllData($opt);
            $doc = array();
            $len = count($data);
            $i = 0;
            $result = array();
            //for now pager is hard coded
            $batch_count = 0;
            try {
                $totalCount = count($data);
                $progress = new ProgressBar($output, $totalCount);
                $progress->setFormat('<info>%message%</info>  <rz-msg-progress>%current%/%max%</rz-msg-progress> [%bar%] <rz-msg-progress>%percent:3s%%</rz-msg-progress> <rz-msg-progress2>%elapsed:6s%/%estimated:-6s%</rz-msg-progress2> <rz-err>%memory:6s%</rz-err>');
                $progress->setRedrawFrequency(10);
                $progress->setBarCharacter('<comment>=</comment>');
                $progress->setEmptyBarCharacter(' ');
                $progress->setProgressCharacter('|');
                $progress->setBarWidth(50);
                $i = 0;
                $progress->setMessage('...');
                $progress->start();
                $progress->clear();
                $progress->display();
                $indexObject = $searchClient->createUpdate();
                foreach($data as $model) {
                    if ($this->getConfigManager()->hasConfig($this->configKey)) {
                        try {
                            $doc[$batch_count] = $indexManager->indexData($modelProcessorService, $indexObject, $model, $this->configKey);
                            // commit every after batch count
                            if ($batch_count >= 10 || ($i == $len - 1)) {
                                // add the documents and a commit command to the update query
                                $indexObject->addDocuments($doc);
                                $indexObject->setOmitHeader(true);
                                $indexObject->addCommit();
                                // this executes the query and returns the result
                                $result[] = $searchClient->update($indexObject);
                                if($batch_count >= 10) {
                                    $batch_count = 0;
                                    $doc = array();
                                }
                            }
                            $batch_count++;
                        } catch (\Exception $e) {
                            throw $e;
                        }
                    }
                    $i++;
                    $progress->setMessage('Indexing in progress...');
                    $progress->advance();
                    sleep(.25);
                }
            } catch(\Exception $e) {
                throw $e;
            }

            $progress->setMessage('Indexing finished');
            $progress->finish();
            $output->writeln(sprintf('<info> Finish indexing %s data!</info>', $totalCount));

            $output->writeln(sprintf('<info>Finish indexing: <rz-msg>%s</rz-msg></info>', $this->configKey));

        } else {
            $output->writeln('<rz-err>Option entity required!</rz-err>');
        }
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @param string          $questionText
     * @param mixed           $default
     * @param callable        $validator
     *
     * @return mixed
     */
    private function promptOption(InputInterface $input, OutputInterface $output, $questionText, $default)
    {
        $questionHelper = $this->getQuestionHelper();
        $question = new Question($questionHelper->getQuestion($questionText, $default), $default);
        return $questionHelper->ask($input, $output, $question);
    }

    /**
     * @return QuestionHelper|DialogHelper
     */
    private function getQuestionHelper()
    {

        $questionHelper = $this->getHelper('question');

        if (!$questionHelper instanceof QuestionHelper) {
            $questionHelper = new QuestionHelper();
            $this->getHelperSet()->set($questionHelper);
        }
        return $questionHelper;
    }

    protected function getCLIOptions($id) {
        $configManager = $this->getConfigManager() ?: $this->getContainer()->get('rz_search.manager.config');
        $options = $configManager->getCLIOptions($id) ?: [];
        return $options;
    }
}
