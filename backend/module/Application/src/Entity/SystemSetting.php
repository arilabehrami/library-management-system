<?php

namespace Application\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'system_settings')]
class SystemSetting{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255, unique: true)]
    private string $settingKey;

    #[ORM\Column(type: 'text')]
    private string $settingValue;

    public function __construct(
        string $settingKey,
        string $settingValue
    ) {
        $this->settingKey = $settingKey;
        $this->settingValue = $settingValue;
    }

    public function getId(): ?int{
        return $this->id;
    }

    public function getSettingKey(): string{
        return $this->settingKey;
    }

    public function setSettingKey(string $settingKey): void{
        $this->settingKey = $settingKey;
    }

    public function getSettingValue(): string{
        return $this->settingValue;
    }

    public function setSettingValue(string $settingValue): void{
        $this->settingValue = $settingValue;
    }
}
