<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain;

use App\Domain\Exception\BookNotAvailableException;
use App\Domain\Exception\LoanNotActiveException;
use App\Domain\Exception\MaxActiveLoansReachedException;
use App\Domain\Exception\MemberHasOverdueLoanException;
use App\Domain\Exception\ReturnNotAwaitingValidationException;
use App\Domain\LoanManager;
use App\Entity\Book;
use App\Entity\Loan;
use App\Entity\User;
use App\Repository\LoanRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\MockClock;

final class LoanManagerTest extends TestCase
{
    private const string NOW = '2026-01-15 10:00:00';

    private LoanRepository&Stub $loans;
    private EntityManagerInterface&MockObject $em;
    private MockClock $clock;
    private LoanManager $manager;

    protected function setUp(): void
    {
        $this->loans = $this->createStub(LoanRepository::class);
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->clock = new MockClock(new \DateTimeImmutable(self::NOW));
        $this->manager = new LoanManager($this->loans, $this->em, $this->clock);
    }

    public function testBorrowCreatesAndPersistsLoanWhenAllRulesPass(): void
    {
        $book = new Book('Le Petit Prince');
        $borrower = new User();

        $this->loans->method('hasOverdueLoans')->willReturn(false);
        $this->loans->method('countActiveForBorrower')->willReturn(0);
        $this->loans->method('isBookOnLoan')->willReturn(false);

        $this->em->expects($this->once())->method('persist')->with($this->isInstanceOf(Loan::class));
        $this->em->expects($this->once())->method('flush');

        $loan = $this->manager->borrow($borrower, $book);

        self::assertSame($book, $loan->getBook());
        self::assertSame($borrower, $loan->getBorrower());
        self::assertTrue($loan->isActive());
    }

    public function testBorrowSetsDueDate21DaysAfterBorrowDate(): void
    {
        $this->loans->method('hasOverdueLoans')->willReturn(false);
        $this->loans->method('countActiveForBorrower')->willReturn(0);
        $this->loans->method('isBookOnLoan')->willReturn(false);
        $this->em->expects($this->once())->method('persist');
        $this->em->expects($this->once())->method('flush');

        $loan = $this->manager->borrow(new User(), new Book('Titre'));

        self::assertEquals(new \DateTimeImmutable(self::NOW), $loan->getBorrowedAt());
        // 21 jours après le 15 janvier => 5 février.
        self::assertEquals(new \DateTimeImmutable('2026-02-05 10:00:00'), $loan->getDueAt());
    }

    public function testBorrowIsRejectedWhenMemberHasOverdueLoan(): void
    {
        $this->loans->method('hasOverdueLoans')->willReturn(true);
        $this->em->expects($this->never())->method('persist');
        $this->em->expects($this->never())->method('flush');

        $this->expectException(MemberHasOverdueLoanException::class);

        $this->manager->borrow(new User(), new Book('Titre'));
    }

    public function testBorrowIsRejectedWhenThreeActiveLoansReached(): void
    {
        $this->loans->method('hasOverdueLoans')->willReturn(false);
        $this->loans->method('countActiveForBorrower')->willReturn(LoanManager::MAX_ACTIVE_LOANS);
        $this->em->expects($this->never())->method('persist');

        $this->expectException(MaxActiveLoansReachedException::class);

        $this->manager->borrow(new User(), new Book('Titre'));
    }

    public function testBorrowIsRejectedWhenBookIsNotAvailable(): void
    {
        $this->loans->method('hasOverdueLoans')->willReturn(false);
        $this->loans->method('countActiveForBorrower')->willReturn(1);
        $this->loans->method('isBookOnLoan')->willReturn(true);
        $this->em->expects($this->never())->method('persist');

        $this->expectException(BookNotAvailableException::class);

        $this->manager->borrow(new User(), new Book('Titre'));
    }

    public function testRequestReturnMovesActiveLoanToReturnRequested(): void
    {
        $loan = new Loan(new Book('Titre'), new User(), new \DateTimeImmutable('2026-01-01 09:00:00'));

        $this->em->expects($this->once())->method('flush');

        $this->manager->requestReturn($loan);

        self::assertTrue($loan->isReturnRequested());
        self::assertEquals(new \DateTimeImmutable(self::NOW), $loan->getReturnRequestedAt());
        self::assertNull($loan->getReturnedAt());
    }

    public function testRequestReturnIsRejectedWhenLoanNotActive(): void
    {
        $loan = new Loan(new Book('Titre'), new User(), new \DateTimeImmutable('2026-01-01 09:00:00'));
        $loan->requestReturn(new \DateTimeImmutable('2026-01-10 09:00:00'));

        $this->em->expects($this->never())->method('flush');

        $this->expectException(LoanNotActiveException::class);

        $this->manager->requestReturn($loan);
    }

    public function testValidateReturnMarksLoanAsReturned(): void
    {
        $loan = new Loan(new Book('Titre'), new User(), new \DateTimeImmutable('2026-01-01 09:00:00'));
        $loan->requestReturn(new \DateTimeImmutable('2026-01-10 09:00:00'));

        $this->em->expects($this->once())->method('flush');

        $this->manager->validateReturn($loan);

        self::assertSame('returned', $loan->getStatus()->value);
        self::assertEquals(new \DateTimeImmutable(self::NOW), $loan->getReturnedAt());
    }

    public function testValidateReturnIsRejectedWhenNoReturnPending(): void
    {
        $loan = new Loan(new Book('Titre'), new User(), new \DateTimeImmutable('2026-01-01 09:00:00'));

        $this->em->expects($this->never())->method('flush');

        $this->expectException(ReturnNotAwaitingValidationException::class);

        $this->manager->validateReturn($loan);
    }
}
