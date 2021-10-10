<?php declare(strict_types=1);

namespace App\Command;

/**
 * Import classes
 */
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

/**
 * Import functions
 */
use function file_put_contents;
use function posix_getcwd;
use function posix_getgid;
use function posix_getgrgid;
use function posix_getpwuid;
use function posix_getuid;
use function sprintf;
use function strtr;

/**
 * GenerateSystemdUnitFile
 */
class GenerateSystemdUnitFile extends Command
{

    /**
     * Systemd unit file template
     *
     * @var string
     *
     * @link https://wiki.debian.org/systemd
     * @link https://wiki.debian.org/systemd/Services
     */
    protected const SYSTEMD_UNIT_TEMPLATE = <<<'EOT'
[Unit]
After=network.target

[Service]
Type=simple
User={user}
Group={group}
Restart=always
WorkingDirectory={dir}
ExecStart=/usr/local/bin/rr serve -dv
ExecReload=/usr/local/bin/rr http:reset
ExecStop=/bin/kill -s TERM $MAINPID

[Install]
WantedBy=multi-user.target

EOT;

    /**
     * {@inheritDoc}
     */
    protected static $defaultName = 'app:generate-systemd-unit-file';

    /**
     * @Inject
     *
     * @var ContainerInterface
     */
    private $container;

    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this->setDescription('Generates an unit file for the systemd service manager');
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $dir = posix_getcwd();
        $user = posix_getpwuid(posix_getuid());
        $group = posix_getgrgid(posix_getgid());
        $questioner = $this->getHelper('question');

        $format = 'The application directory [<fg=yellow>%s</>]: ';
        $question = new Question(sprintf($format, $dir), $dir);
        $context['{dir}'] = $questioner->ask($input, $output, $question);

        $format = 'Which user should run the application [<fg=yellow>%s</>]: ';
        $question = new Question(sprintf($format, $user['name']), $user['name']);
        $context['{user}'] = $questioner->ask($input, $output, $question);

        $format = 'Which group should have the user [<fg=yellow>%s</>]: ';
        $question = new Question(sprintf($format, $group['name']), $group['name']);
        $context['{group}'] = $questioner->ask($input, $output, $question);

        $content = strtr(self::SYSTEMD_UNIT_TEMPLATE, $context);
        $filename = $dir . '/app.' . $this->container->get('name') . '.service';

        file_put_contents($filename, $content);

        $output->writeln('<fg=green>Successful</> [<fg=yellow>' . $filename . '</>]');
    }
}
