<?php

namespace App\Command;

use App\Document\Customer;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:makeAdmin',
    description: 'Add a short description for your command',
)]
class MakeAdminCommand extends Command
{
    private DocumentManager $dm;

    public function __construct(DocumentManager $dm)
    {
        $this->dm = $dm;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('arg1', InputArgument::OPTIONAL, 'Argument description')
            ->addOption('option1', null, InputOption::VALUE_NONE, 'Option description')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $arg1 = $input->getArgument('arg1');

        if ($arg1) {
            if($user = $this->dm->getRepository(Customer::class)->findOneBy(['username' => $arg1])){
                    $user->setRoles(['ROLE_USER','ROLE_ADMIN']);
                    $this->dm->flush();
                    $io->success('User promoted!');
                    return Command::SUCCESS;
                }
            $io->success('Unknown user');
            return Command::FAILURE;
        }
        else{
            $io->success('Invalid arguments');
            return Command::FAILURE;
        }
    }
}
