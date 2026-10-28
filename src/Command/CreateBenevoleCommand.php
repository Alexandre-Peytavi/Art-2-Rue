<?php

namespace App\Command;

use App\Entity\Benevole;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-benevole',
    description: 'Crée un compte bénévole de test'
)]
class CreateBenevoleCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ): int {
        $io = new SymfonyStyle($input, $output);
        $helper = $this->getHelper('question');

        $email = 'test@art2rue.fr';

        $existing = $this->entityManager
            ->getRepository(Benevole::class)
            ->findOneBy(['email' => $email]);

        if ($existing) {
            $io->warning('Le compte de test existe déjà.');
            return Command::SUCCESS;
        }

        $benevole = new Benevole();
        $benevole->setNom('Test');
        $benevole->setPrenom('Benevole');
        $benevole->setEmail($email);

        $question = new Question('Mot de passe du compte de test : ');
        $question->setHidden(true);
        $question->setHiddenFallback(false);

        $plainPassword = $helper->ask($input, $output, $question);

        if (!is_string($plainPassword) || $plainPassword === '') {
            $io->error('Le mot de passe ne peut pas être vide.');
            return Command::FAILURE;
        }

        $hashedPassword = $this->passwordHasher->hashPassword(
            $benevole,
            $plainPassword
        );

        $benevole->setPassword($hashedPassword);

        $this->entityManager->persist($benevole);
        $this->entityManager->flush();

        $io->success('Compte créé : ' . $email);
        $io->note('Le mot de passe a été hashé avant son enregistrement.');

        return Command::SUCCESS;
    }
}
