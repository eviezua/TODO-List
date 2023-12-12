<?php

namespace App\ApiResource;

use ApiPlatform\Doctrine\Orm\Filter\NumericFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Doctrine\Orm\State\Options;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use App\Entity\Status;
use App\Entity\Task;
use App\State\EntityClassDtoStateProcessor;
use App\State\EntityToDtoStateProvider;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    shortName: 'Task',
    operations: [
        new Get(),
        new GetCollection(),
        new Post(),
        new Put(),
        new Patch(
            inputFormats: ['json' => ['application/merge-patch+json']],
            security: 'is_granted("ROLE_TASK_EDIT", object)',
        ),
        new Delete(
            security: 'is_granted("ROLE_TASK_DELETE", object)',
        ),
    ],
    security: 'is_granted("ROLE_USER")',
    provider: EntityToDtoStateProvider::class,
    processor: EntityClassDtoStateProcessor::class,
    stateOptions: new Options(entityClass: Task::class)),
]
class TaskApi
{
    #[ApiProperty(readable: false, writable: false, identifier: true)]
    public ?int $id = null;

    #[ApiFilter(NumericFilter::class, strategy: 'exact')]
    #[Assert\NotBlank]
    #[Assert\Range(min: 1, max: 5)]
    public ?int $priority = null;

    #[ApiFilter(SearchFilter::class, strategy: 'ipartial')]
    #[Assert\NotBlank]
    public ?string $title = null;

    #[ApiFilter(SearchFilter::class, strategy: 'ipartial')]
    public ?string $description = null;

    #[ApiFilter(SearchFilter::class, strategy: 'iexact')]
    #[Assert\Valid]
    public ?Status $status = null;

    public ?UserApi $owner = null;
}