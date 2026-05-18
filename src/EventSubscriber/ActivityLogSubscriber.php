<?php

namespace App\EventSubscriber;

use App\Entity\ActivityLog;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\Event\LogoutEvent;
use Symfony\Component\Security\Http\SecurityEvents;

class ActivityLogSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            SecurityEvents::INTERACTIVE_LOGIN => 'onLogin',
            LogoutEvent::class => 'onLogout',
        ];
    }

    public function onLogin(InteractiveLoginEvent $event): void
    {
        $user = $event->getAuthenticationToken()->getUser();
        
        if ($user instanceof User) {
            $this->logActivity(
                $user,
                'Login',
                null,
                null,
                'User logged in'
            );
        }
    }

    public function onLogout(LogoutEvent $event): void
    {
        $token = $event->getToken();
        
        if ($token && $token->getUser() instanceof User) {
            $user = $token->getUser();
            $this->logActivity(
                $user,
                'Logout',
                null,
                null,
                'User logged out'
            );
        }
    }

    public function onController(ControllerEvent $event): void
    {
        // This will be handled by individual controllers for Create/Update/Delete
        // We'll add logging directly in controllers for better control
    }

    private function logActivity(
        User $user,
        string $action,
        ?string $entityType = null,
        ?int $entityId = null,
        ?string $description = null
    ): void {
        $log = new ActivityLog();
        $log->setUser($user);
        
        // Get the primary role (first role that's not ROLE_USER)
        $roles = $user->getRoles();
        $primaryRole = 'ROLE_USER';
        foreach ($roles as $role) {
            if ($role !== 'ROLE_USER') {
                $primaryRole = $role;
                break;
            }
        }
        
        $log->setRole($primaryRole);
        $log->setAction($action);
        $log->setEntityType($entityType);
        $log->setEntityId($entityId);
        $log->setDescription($description);

        $this->entityManager->persist($log);
        $this->entityManager->flush();
    }

    /**
     * Public method to log activities from controllers
     */
    public function log(
        User $user,
        string $action,
        ?string $entityType = null,
        ?int $entityId = null,
        ?string $description = null
    ): void {
        $this->logActivity($user, $action, $entityType, $entityId, $description);
    }
}

