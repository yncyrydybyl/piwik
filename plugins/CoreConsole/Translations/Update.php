<?php
/**
 * Piwik - Open source web analytics
 *
 * @link     http://piwik.org
 * @license  http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 * @category Piwik_Plugins
 * @package  CoreConsole
 */

namespace Piwik\Plugins\CoreConsole\Translations;

use Piwik\Console\Command;
use Piwik\Plugins\LanguagesManager\API;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @package CoreConsole
 */
class Update extends Command
{
    protected function configure()
    {
        $this->setName('translations:update')
            ->setDescription('Updates translation files')
            ->addOption('username', 'u', InputOption::VALUE_OPTIONAL, 'oTrance username')
            ->addOption('password', 'p', InputOption::VALUE_OPTIONAL, 'oTrance password')
            ->addOption('plugin', 'l', InputOption::VALUE_OPTIONAL, 'optional name of plugin to update translations for');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $dialog = $this->getHelperSet()->get('dialog');

        $command = $this->getApplication()->find('translations:fetch');
        $arguments = array(
            'command'    => 'translations:fetch',
            '--username' => $input->getOption('username'),
            '--password' => $input->getOption('password'),
        );
        $command->run(new ArrayInput($arguments), $output);

        $languages = API::getInstance()->getAvailableLanguageNames();

        $languageCodes = array();
        foreach ($languages AS $languageInfo) {
            $languageCodes[] = $languageInfo['code'];
        }

        $plugin = $input->getOption('plugin');

        $files = _glob(FetchFromOTrance::getDownloadPath() . DIRECTORY_SEPARATOR . '*.json');

        $output->writeln("Starting to import new language files");

        $progress = $this->getHelperSet()->get('progress');

        $progress->start($output, count($files));

        foreach ($files AS $filename) {

            $progress->advance();

            $code = basename($filename, '.json');

            if (!in_array($code, $languageCodes)) {

                if (!empty($plugin)) {

                    continue; # never create a new language for plugin only
                }

                $createNewFile = $dialog->askConfirmation($output, "\nLanguage $code does not exist. Should it be added? ");

                if (!$createNewFile) {

                    continue; # do not create a new file for the language
                }

            }

            $command = $this->getApplication()->find('translations:set');
            $arguments = array(
                'command'  => 'translations:set',
                '--code'   => $code,
                '--file'   => $filename,
                '--plugin' => $plugin
            );
            $command->run(new ArrayInput($arguments), new NullOutput());
        }

        $progress->finish();
        $output->writeln("Finished.");
    }
}