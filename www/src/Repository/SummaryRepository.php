<?php
// src/Repository/ChatHistoryRepository.php

namespace App\Repository;

use App\Entity\Summary;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class SummaryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Summary::class);
    }
}