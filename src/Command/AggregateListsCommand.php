<?php

namespace App\Command;

use App\Entity\AlbumList;
use App\EventSubscriber\AlbumListAggregationSubscriber;
use App\Repository\AlbumListRepository;
use App\Service\ListAggregator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:aggregate-lists',
    description: 'Computes aggregation for all lists of type "aggregate"',
)]
class AggregateListsCommand extends Command
{
    public function __construct(
        private AlbumListRepository $albumListRepository,
        private ListAggregator $listAggregator,
        private EntityManagerInterface $entityManager,
        private AlbumListAggregationSubscriber $subscriber,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Disable logging to save memory
        $this->entityManager->getConnection()->getConfiguration()->setMiddlewares([]);
        
        // Disable subscriber to avoid double aggregation and recursion during bulk update
        $this->subscriber->setEnabled(false);

        $io = new SymfonyStyle($input, $output);
        $io->title('Aggregating Lists');

        // 1. Fetch all aggregate list IDs first
        $io->section('Fetching aggregate lists...');
        
        $qb = $this->albumListRepository->createQueryBuilder('al');
        $qb->select('al.id')
           ->where('al.type = :type')
           ->setParameter('type', AlbumList::TYPE_AGGREGATE);
        
        $ids = $qb->getQuery()->getSingleColumnResult();
        
        $count = count($ids);
        $io->text(sprintf('Found %d aggregate lists.', $count));

        if ($count === 0) {
            $io->success('No aggregate lists found.');
            return Command::SUCCESS;
        }

        // 2. Process lists one by one
        $io->progressStart($count);

        foreach ($ids as $id) {
            try {
                // Clear EM to free memory from previous iterations
                $this->entityManager->clear();
                
                // Refetch the list
                $list = $this->albumListRepository->find($id);
                
                if (!$list) {
                    continue;
                }

                // The aggregator wipes existing items and creates new ones
                $this->listAggregator->aggregate($list);
                
                $this->entityManager->flush();
                
                // Explicitly detach to be sure
                $this->entityManager->detach($list);

            } catch (\Exception $e) {
                $io->error(sprintf('Error aggregating list ID %d: %s', $id, $e->getMessage()));
            }

            $io->progressAdvance();
        }

        $io->progressFinish();
        $io->success('Aggregation completed.');

        return Command::SUCCESS;
    }
}
