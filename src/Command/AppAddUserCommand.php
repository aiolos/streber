<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class AppAddUserCommand extends Command
{
    protected static $defaultName = 'app:add-user';
    private $entityManager;
    private $passwordEncoder;

    public function __construct(EntityManagerInterface $entityManager, UserPasswordEncoderInterface $passwordEncoder)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->passwordEncoder = $passwordEncoder;
    }

    protected function configure()
    {
        $this
            ->setDescription('Add a short description for your command');
        ;
        $this->addArgument('email', InputArgument::REQUIRED, 'The email of the user.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Username: ' . $input->getArgument('email'));


        $io = new SymfonyStyle($input, $output);
        $password = $io->ask('Password:');

        $user = new User();
        $user->setEmail($input->getArgument('email'));
        $user->setPassword($this->passwordEncoder->encodePassword($user, $password));
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $io->success('User ' . $user->getEmail() . ' saved');
    }
}
