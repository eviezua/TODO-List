<?php

namespace App\Security\Voter;

use App\ApiResource\TaskApi;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

class TaskVoter extends Voter
{
    public const EDIT = 'ROLE_TASK_EDIT';
    public const DELETE = 'ROLE_TASK_DELETE';

    public function __construct(private Security $security)
    {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::EDIT, self::DELETE])
            && $subject instanceof TaskApi;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof UserInterface) {
            return false;
        }

        assert($subject instanceof TaskApi);

        switch ($attribute) {
            case self::EDIT:
            case self::DELETE:
                if (!$this->security->isGranted('ROLE_USER')) {
                    return false;
                }

                if ($subject->owner->id === $user->getId()) {
                    return true;
                }
                break;
        }

        return false;
    }
}
