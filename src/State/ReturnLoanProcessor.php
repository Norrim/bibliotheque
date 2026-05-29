<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\ApiResource\LoanOutput;
use App\Domain\Exception\DomainException;
use App\Domain\LoanManager;
use App\Repository\LoanRepository;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Traite la restitution d'un emprunt (action réservée au bibliothécaire).
 *
 * @implements ProcessorInterface<mixed, LoanOutput>
 */
final class ReturnLoanProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly LoanRepository $loans,
        private readonly LoanManager $loanManager,
        private readonly LoanOutputMapper $mapper,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): LoanOutput
    {
        $loan = $this->loans->find((int) ($uriVariables['id'] ?? 0));
        if (null === $loan) {
            throw new NotFoundHttpException('Emprunt introuvable.');
        }

        try {
            $this->loanManager->returnBook($loan);
        } catch (DomainException $e) {
            throw new ConflictHttpException($e->getMessage(), $e);
        }

        return $this->mapper->map($loan);
    }
}
