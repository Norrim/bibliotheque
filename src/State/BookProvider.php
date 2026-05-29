<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Doctrine\Orm\State\CollectionProvider;
use ApiPlatform\Doctrine\Orm\State\ItemProvider;
use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Pagination\PaginatorInterface;
use ApiPlatform\State\Pagination\TraversablePaginator;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\BookOutput;
use App\Entity\Book;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Fournit les ressources Book en déléguant la récupération (pagination, filtres)
 * aux providers Doctrine d'API Platform, puis en mappant les entités vers le DTO
 * BookOutput. L'entité n'est ainsi jamais exposée directement.
 *
 * @implements ProviderInterface<BookOutput>
 */
final class BookProvider implements ProviderInterface
{
    /**
     * @param ProviderInterface<Book> $collectionProvider
     * @param ProviderInterface<Book> $itemProvider
     */
    public function __construct(
        #[Autowire(service: CollectionProvider::class)]
        private readonly ProviderInterface $collectionProvider,
        #[Autowire(service: ItemProvider::class)]
        private readonly ProviderInterface $itemProvider,
        private readonly BookOutputMapper $mapper,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        if ($operation instanceof CollectionOperationInterface) {
            $books = $this->collectionProvider->provide($operation, $uriVariables, $context);

            $items = [];
            if (is_iterable($books)) {
                foreach ($books as $book) {
                    $items[] = $this->mapper->map($book);
                }
            }

            if ($books instanceof PaginatorInterface) {
                return new TraversablePaginator(
                    new \ArrayIterator($items),
                    $books->getCurrentPage(),
                    $books->getItemsPerPage(),
                    $books->getTotalItems(),
                );
            }

            return $items;
        }

        $book = $this->itemProvider->provide($operation, $uriVariables, $context);

        return $book instanceof Book ? $this->mapper->map($book) : null;
    }
}
