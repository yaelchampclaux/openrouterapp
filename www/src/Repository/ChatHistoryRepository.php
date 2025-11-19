<?php
// src/Repository/ChatHistoryRepository.php

namespace App\Repository;

use App\Entity\ChatHistory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ChatHistoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ChatHistory::class);
    }
}