<?php
namespace osim\craft\focus\models;

use Craft;
use craft\base\Model;
use craft\helpers\UrlHelper;
use DateTime;
use osim\craft\focus\Plugin;

class OsimFocusProject extends Model
{
    public ?string $uid = null;
    public ?string $name = null;
    public ?string $description = null;
    public ?bool $default = null;
    public ?int $priority = null;
    public ?int $certainty = null;
    public ?bool $wcag = null;
    public ?string $wcagLevel = null;
    public ?bool $bestPractice = null;
    public ?bool $store = null;
    public ?string $userAgent = null;
    public ?int $viewportWidth = null;
    public ?int $viewportHeight = null;
    public ?int $delay = null;
    public ?DateTime $dateCreated = null;
    public ?DateTime $dateUpdated = null;

    public function getOptionName(): string
    {
        return $this->name . ' [' . $this->id . ']';
    }

    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['name'], 'required'];

        return $rules;
    }

    public function getTestApiData(): array
    {
        $data = [
            'project_uid' => $this->uid,
            'priority' => $this->priority,
            'certainty' => $this->certainty,
            'wcag' => $this->wcag,
            'wcag_level' => $this->wcagLevel,
            'best_practice' => $this->bestPractice,
            'store' => $this->store,
            'userAgent' => $this->userAgent,
            'viewport_width' => $this->viewportWidth,
            'viewport_height' => $this->viewportHeight,
            'delay' => $this->delay,
        ];

        foreach ($data as $key => $value) {
            if ($value === null) {
                unset($data[$key]);
            }
        }

        return $data;
    }
    public function getProjectApiData(): array
    {
        $data = [
            'name' => $this->name,
            'description' => $this->description,
            'default' => $this->default,
            'certainty' => $this->certainty,
            'priority' => $this->priority,
            'wcag' => $this->wcag,
            'wcag_level' => $this->wcagLevel,
            'best_practice' => $this->bestPractice,
            'store' => $this->store,
            'user_agent' => $this->userAgent,
            'viewport_width' => $this->viewportWidth,
            'viewport_height' => $this->viewportHeight,
            'delay' => $this->delay,
        ];

        foreach ($data as $key => $value) {
            if ($value === null) {
                unset($data[$key]);
            }
        }

        return $data;
    }
}
