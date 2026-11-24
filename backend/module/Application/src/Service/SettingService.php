<?php

declare(strict_types=1);

namespace Application\Service;

use Application\Entity\SystemLog;
use Application\Entity\SystemSetting;
use Application\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

final class SettingService
{
    private const RULES = [
        'borrow_fee'       => 'decimal',
        'penalty_rate'     => 'decimal',
        'borrowing_period' => 'int',
        'borrowing_limit'  => 'int',
    ];

    public function __construct(
        private readonly EntityManagerInterface $em
    ) {
    }

    public function all(User $actor): array
    {
        $this->requireSuperAdmin($actor);

        $out = [];
        foreach ($this->em->getRepository(SystemSetting::class)->findAll() as $s) {
            $out[$s->getSettingKey()] = $s->getSettingValue();
        }

        return $out;
    }

    public function update(User $actor, string $key, string $value, \DateTimeImmutable $now): SystemSetting
    {
        $this->requireSuperAdmin($actor);

        if (!array_key_exists($key, self::RULES)) {
            throw new \DomainException('Cilësim i panjohur.');
        }
        $this->validate($key, $value);

        $setting = $this->em->getRepository(SystemSetting::class)
            ->findOneBy(['settingKey' => $key]);
        if ($setting === null) {
            throw new \DomainException('Cilësimi nuk u gjet.');
        }

        $old = $setting->getSettingValue();
        $setting->setSettingValue($value);

        $this->em->persist(new SystemLog(
            actor: $actor,
            actionType: 'setting.updated',
            entityType: 'SystemSetting',
            entityId: $setting->getId(),
            details: json_encode(['key' => $key, 'old' => $old, 'new' => $value], JSON_THROW_ON_ERROR)
        ));
        $this->em->flush();

        return $setting;
    }

    private function validate(string $key, string $value): void
    {
        $ok = match (self::RULES[$key]) {
            'decimal' => preg_match('/^\d{1,8}(\.\d{1,2})?$/', $value) === 1,
            'int'     => ctype_digit($value) && (int) $value >= 1 && (int) $value <= 36500,
        };

        if (!$ok) {
            throw new \DomainException("Vlerë e pavlefshme për $key.");
        }
    }

    private function requireSuperAdmin(User $actor): void
    {
        if ($actor->getRole() !== 'super_admin') {
            throw new \DomainException('Vetëm super admin mund t\'i shohë ose ndryshojë cilësimet.');
        }
    }
}