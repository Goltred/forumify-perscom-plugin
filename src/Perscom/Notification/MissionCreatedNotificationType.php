<?php

declare(strict_types=1);

namespace Forumify\PerscomPlugin\Perscom\Notification;

use Forumify\Core\Entity\Notification;
use Forumify\Core\Notification\AbstractEmailNotificationType;
use Forumify\PerscomPlugin\Perscom\Entity\Mission;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class MissionCreatedNotificationType extends AbstractEmailNotificationType
{
    public const TYPE = 'perscom_mission_created';

    public function __construct(
        private readonly Packages $packages,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly TranslatorInterface $translator,
    ) {
    }

    public function getType(): string
    {
        return self::TYPE;
    }

    public function getTitle(Notification $notification): string
    {
        $mission = $this->getMission($notification);
        $operation = $mission?->getOperation()->getTitle() ?? 'unknown';

        return $this->translator->trans('perscom.notification.mission_created.title', [
            'operation' => $operation,
        ]);
    }

    public function getDescription(Notification $notification): string
    {
        $mission = $this->getMission($notification)?->getTitle() ?? 'unknown';

        return $this->translator->trans('perscom.notification.mission_created.description', [
            'mission' => $mission,
        ]);
    }

    public function getImage(Notification $notification): string
    {
        $image = $this->getMission($notification)?->getOperation()->getImage();
        if ($image !== null) {
            return $this->packages->getUrl($image, 'forumify.asset');
        }

        return $this->packages->getUrl('bundles/forumifyperscomplugin/images/perscom.png');
    }

    public function getUrl(Notification $notification): string
    {
        $missionId = $this->getMission($notification)?->getId();
        if ($missionId === null) {
            return '';
        }

        return $this->urlGenerator->generate('perscom_missions_view', ['id' => $missionId]);
    }

    public function getEmailTemplate(Notification $notification): string
    {
        return '@ForumifyPerscomPlugin/emails/notifications/mission_created.html.twig';
    }

    private function getMission(Notification $notification): ?Mission
    {
        $mission = $notification->getDeserializedContext()['mission'] ?? null;
        if (!$mission instanceof Mission) {
            return null;
        }
        return $mission;
    }
}
