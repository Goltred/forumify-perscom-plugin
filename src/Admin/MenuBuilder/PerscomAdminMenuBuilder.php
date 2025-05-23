<?php

declare(strict_types=1);

namespace Forumify\PerscomPlugin\Admin\MenuBuilder;

use DateInterval;
use Forumify\Admin\MenuBuilder\AdminMenuBuilderInterface;
use Forumify\Core\MenuBuilder\Menu;
use Forumify\Core\MenuBuilder\MenuItem;
use Forumify\PerscomPlugin\Perscom\PerscomFactory;
use Forumify\Plugin\Service\PluginVersionChecker;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class PerscomAdminMenuBuilder implements AdminMenuBuilderInterface
{
    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly PerscomFactory $perscomFactory,
        private readonly CacheInterface $cache,
        private readonly PluginVersionChecker $pluginVersionChecker,
    ) {
    }

    public function build(Menu $menu): void
    {
        $u = $this->urlGenerator->generate(...);

        $submissionMenu = new Menu('Submissions', ['icon' => 'ph ph-table', 'permission' => 'perscom-io.admin.submissions.view']);
        $submissionMenu->addItem(new MenuItem('View All', $u('perscom_admin_submission_list')));
        foreach ($this->getSubmissionForms() as $form) {
            $submissionMenu->addItem(new MenuItem($form, $u('perscom_admin_submission_list', ['form' => $form])));
        }

        $perscomMenu = new Menu('PERSCOM', ['icon' => 'ph ph-shield-chevron', 'permission' => 'perscom-io.admin.view'], [
            new MenuItem('Configuration', $u('perscom_admin_configuration'), ['icon' => 'ph ph-wrench', 'permission' => 'perscom-io.admin.configuration.manage']),
            new MenuItem('Users', $u('perscom_admin_user_list'), ['icon' => 'ph ph-users', 'permission' => 'perscom-io.admin.users.view']),
            $submissionMenu,
        ]);

        if ($this->pluginVersionChecker->isVersionInstalled('forumify/forumify-perscom-plugin', 'premium')) {
            $perscomMenu
                ->addItem(new MenuItem('Operations', $u('perscom_admin_operations_list'), [
                    'icon' => 'ph ph-airplane-takeoff',
                    'permission' => 'perscom-io.admin.operations.view',
                ]))
                ->addItem(new MenuItem('Courses', $u('perscom_admin_courses_list'), [
                    'icon' => 'ph ph-graduation-cap',
                    'permission' => 'perscom-io.admin.courses.view',
                ]));
        }

        $perscomMenu->addItem(new Menu('Organization', ['icon' => 'ph ph-buildings', 'permission' => 'perscom-io.admin.organization.view'], [
            new MenuItem('Units', $u('perscom_admin_unit_list')),
            new MenuItem('Positions', $u('perscom_admin_position_list')),
            new MenuItem('Specialties', $u('perscom_admin_specialty_list')),
            new MenuItem('Statuses', $u('perscom_admin_status_list')),
            new MenuItem('Awards', $u('perscom_admin_award_list')),
            new MenuItem('Qualifications', $u('perscom_admin_qualification_list')),
        ]));

        $menu->addItem($perscomMenu);
    }

    private function getSubmissionForms(): array
    {
        return $this->cache->get('perscom.admin.forms', function (ItemInterface $item): array {
            try {
                $forms = $this->perscomFactory->getPerscom()->forms()->all(limit: 999)->json()['data'] ?? [];
            } catch (\Exception) {
                $item->expiresAfter(new DateInterval('PT15M'));
                return [];
            }

            $item->expiresAfter(new DateInterval('PT1H'));
            return array_combine(array_column($forms, 'id'), array_column($forms, 'name'));
        });
    }
}
