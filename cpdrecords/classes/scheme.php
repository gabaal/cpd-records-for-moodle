<?php
// This file is part of CPD Records for Moodle.
//
// CPD Records for Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Validated CPD scheme definitions and included presets.
 *
 * @package    local_cpdrecords
 * @copyright  2026 CPD Records contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_cpdrecords;

/**
 * Validates scheme configuration without depending on Moodle APIs.
 */
class scheme {
    /**
     * Return the verified GDC preset.
     *
     * @return array
     */
    public static function gdc(): array {
        return self::validate([
            'shortname' => 'gdc',
            'name' => 'GDC Enhanced CPD',
            'description' => 'General Dental Council Enhanced CPD scheme.',
            'cyclelength' => 5,
            'yearstart' => '01-01',
            'roles' => [
                'dentist' => [
                    'label' => 'Dentist',
                    'hours' => 100,
                ],
                'therapist' => [
                    'label' => 'Dental therapist',
                    'hours' => 75,
                    'yearstart' => '08-01',
                ],
                'hygienist' => [
                    'label' => 'Dental hygienist',
                    'hours' => 75,
                    'yearstart' => '08-01',
                ],
                'orthodontic_therapist' => [
                    'label' => 'Orthodontic therapist',
                    'hours' => 75,
                    'yearstart' => '08-01',
                ],
                'clinical_dental_technician' => [
                    'label' => 'Clinical dental technician',
                    'hours' => 75,
                    'yearstart' => '08-01',
                ],
                'dental_nurse' => [
                    'label' => 'Dental nurse',
                    'hours' => 50,
                    'yearstart' => '08-01',
                ],
                'dental_technician' => [
                    'label' => 'Dental technician',
                    'hours' => 50,
                    'yearstart' => '08-01',
                ],
            ],
            'categories' => [
                'A' => 'Effective communication with patients, team and others',
                'B' => 'Effective management of self and others, teamwork and leadership',
                'C' => 'Maintenance and development of knowledge and skill within your field of practice',
                'D' => 'Maintaining skills, behaviours and attitudes that keep patient confidence',
            ],
            'minimumhours' => 10,
            'minimumyears' => 2,
            'declarationdays' => 28,
            'enabled' => 1,
        ]);
    }

    /**
     * Return an editable, non-regulatory Custom CPD starting template.
     *
     * @return array
     */
    public static function custom(): array {
        return self::validate([
            'shortname' => 'custom',
            'name' => 'Custom CPD',
            'description' => 'Starting template only. Configure the roles and rules before use.',
            'cyclelength' => 1,
            'yearstart' => '01-01',
            'roles' => [
                'professional' => [
                    'label' => 'Professional',
                    'hours' => 20,
                ],
            ],
            'categories' => [],
            'minimumhours' => null,
            'minimumyears' => null,
            'declarationdays' => null,
            'enabled' => 1,
        ]);
    }

    /**
     * Validate and normalise a CPD scheme definition.
     *
     * @param array $definition Raw scheme definition.
     * @return array
     * @throws \InvalidArgumentException When the definition is invalid.
     */
    public static function validate(array $definition): array {
        $normalised = [
            'shortname' => trim((string)($definition['shortname'] ?? '')),
            'name' => trim((string)($definition['name'] ?? '')),
            'description' => trim((string)($definition['description'] ?? '')),
            'cyclelength' => self::integer($definition['cyclelength'] ?? null),
            'yearstart' => trim((string)($definition['yearstart'] ?? '')),
            'roles' => [],
            'categories' => [],
            'minimumhours' => null,
            'minimumyears' => null,
            'declarationdays' => null,
            'enabled' => empty($definition['enabled']) ? 0 : 1,
        ];

        if (!preg_match('/^[a-z][a-z0-9_]{0,99}$/D', $normalised['shortname'])) {
            throw new \InvalidArgumentException('Scheme shortname must be a lowercase stable key.');
        }
        if ($normalised['name'] === '') {
            throw new \InvalidArgumentException('Scheme name is required.');
        }
        if ($normalised['cyclelength'] === null || $normalised['cyclelength'] < 1 || $normalised['cyclelength'] > 10) {
            throw new \InvalidArgumentException('Cycle length must be between 1 and 10 years.');
        }
        if (!self::valid_year_start($normalised['yearstart'])) {
            throw new \InvalidArgumentException('CPD-year start must be a valid MM-DD date.');
        }

        $roles = $definition['roles'] ?? null;
        if (!is_array($roles) || $roles === []) {
            throw new \InvalidArgumentException('At least one professional role is required.');
        }
        foreach ($roles as $key => $role) {
            $key = (string)$key;
            if (!preg_match('/^[a-z][a-z0-9_]{0,39}$/D', $key) || !is_array($role)) {
                throw new \InvalidArgumentException('Each role needs a lowercase stable key and definition.');
            }
            $label = trim((string)($role['label'] ?? ''));
            $hours = $role['hours'] ?? null;
            if ($label === '' || !is_numeric($hours) || (float)$hours <= 0 || (float)$hours > 10000) {
                throw new \InvalidArgumentException("Role {$key} needs a label and a positive hour target.");
            }
            $normalised['roles'][$key] = [
                'label' => $label,
                'hours' => round((float)$hours, 2),
            ];
            if (array_key_exists('yearstart', $role) && $role['yearstart'] !== '') {
                $rolestart = trim((string)$role['yearstart']);
                if (!self::valid_year_start($rolestart)) {
                    throw new \InvalidArgumentException("Role {$key} has an invalid CPD-year start.");
                }
                $normalised['roles'][$key]['yearstart'] = $rolestart;
            }
        }

        $categories = $definition['categories'] ?? [];
        if (!is_array($categories)) {
            throw new \InvalidArgumentException('Categories must be a keyed array.');
        }
        foreach ($categories as $key => $label) {
            $key = (string)$key;
            $label = trim((string)$label);
            if (!preg_match('/^[A-Za-z][A-Za-z0-9_-]{0,39}$/D', $key) || $label === '') {
                throw new \InvalidArgumentException('Each category needs a stable key and label.');
            }
            $normalised['categories'][$key] = $label;
        }

        $minimumhours = self::nullable_value($definition['minimumhours'] ?? null);
        $minimumyears = self::nullable_value($definition['minimumyears'] ?? null);
        if (($minimumhours === null) !== ($minimumyears === null)) {
            throw new \InvalidArgumentException('Minimum hours and window years must be configured together.');
        }
        if ($minimumhours !== null) {
            $years = self::integer($minimumyears);
            if (!is_numeric($minimumhours) || (float)$minimumhours <= 0 || $years === null || $years < 2 || $years > 10) {
                throw new \InvalidArgumentException('The consecutive-year minimum is invalid.');
            }
            $normalised['minimumhours'] = round((float)$minimumhours, 2);
            $normalised['minimumyears'] = $years;
        }

        $declarationdays = self::nullable_value($definition['declarationdays'] ?? null);
        if ($declarationdays !== null) {
            $days = self::integer($declarationdays);
            if ($days === null || $days < 0 || $days > 365) {
                throw new \InvalidArgumentException('Declaration offset must be between 0 and 365 days.');
            }
            $normalised['declarationdays'] = $days;
        }

        return $normalised;
    }

    /**
     * Return one role from an already validated scheme.
     *
     * @param array $scheme Validated scheme.
     * @param string $rolekey Role key.
     * @return array|null
     */
    public static function role(array $scheme, string $rolekey): ?array {
        return $scheme['roles'][$rolekey] ?? null;
    }

    /**
     * Convert role definitions to editable configuration lines.
     *
     * @param array $roles Role definitions.
     * @return string
     */
    public static function roles_to_text(array $roles): string {
        $lines = [];
        foreach ($roles as $key => $role) {
            $line = $key . ' | ' . $role['label'] . ' | ' . $role['hours'];
            if (!empty($role['yearstart'])) {
                $line .= ' | ' . $role['yearstart'];
            }
            $lines[] = $line;
        }
        return implode("\n", $lines);
    }

    /**
     * Parse role lines in the form key | label | hours | optional MM-DD.
     *
     * @param string $text Configuration lines.
     * @return array
     */
    public static function roles_from_text(string $text): array {
        $roles = [];
        foreach (self::configuration_lines($text) as $number => $line) {
            $parts = array_map('trim', explode('|', $line));
            if (count($parts) < 3 || count($parts) > 4) {
                throw new \InvalidArgumentException("Role line {$number} must be key | label | hours | optional MM-DD.");
            }
            $role = [
                'label' => $parts[1],
                'hours' => $parts[2],
            ];
            if (isset($parts[3]) && $parts[3] !== '') {
                $role['yearstart'] = $parts[3];
            }
            if (array_key_exists($parts[0], $roles)) {
                throw new \InvalidArgumentException("Role line {$number} duplicates key {$parts[0]}.");
            }
            $roles[$parts[0]] = $role;
        }
        return $roles;
    }

    /**
     * Convert category definitions to editable configuration lines.
     *
     * @param array $categories Category definitions.
     * @return string
     */
    public static function categories_to_text(array $categories): string {
        $lines = [];
        foreach ($categories as $key => $label) {
            $lines[] = $key . ' | ' . $label;
        }
        return implode("\n", $lines);
    }

    /**
     * Parse category lines in the form key | label.
     *
     * @param string $text Configuration lines.
     * @return array
     */
    public static function categories_from_text(string $text): array {
        $categories = [];
        foreach (self::configuration_lines($text) as $number => $line) {
            $parts = array_map('trim', explode('|', $line));
            if (count($parts) !== 2) {
                throw new \InvalidArgumentException("Category line {$number} must be key | label.");
            }
            if (array_key_exists($parts[0], $categories)) {
                throw new \InvalidArgumentException("Category line {$number} duplicates key {$parts[0]}.");
            }
            $categories[$parts[0]] = $parts[1];
        }
        return $categories;
    }

    /**
     * Return non-empty configuration lines keyed by their original line number.
     *
     * @param string $text Configuration text.
     * @return array
     */
    private static function configuration_lines(string $text): array {
        $lines = [];
        foreach (preg_split('/\r\n|\r|\n/', $text) as $index => $line) {
            if (trim($line) !== '') {
                $lines[$index + 1] = trim($line);
            }
        }
        return $lines;
    }

    /**
     * Convert an empty optional value to null.
     *
     * @param mixed $value Raw value.
     * @return mixed
     */
    private static function nullable_value($value) {
        if (is_string($value)) {
            $value = trim($value);
        }
        return $value === '' ? null : $value;
    }

    /**
     * Return an integer only when the input is an exact integer representation.
     *
     * @param mixed $value Raw value.
     * @return int|null
     */
    private static function integer($value): ?int {
        if (is_int($value)) {
            return $value;
        }
        if (is_string($value) && preg_match('/^-?\d+$/D', $value)) {
            return (int)$value;
        }
        return null;
    }

    /**
     * Validate an MM-DD date, including 29 February.
     *
     * @param string $value Month/day value.
     * @return bool
     */
    private static function valid_year_start(string $value): bool {
        if (!preg_match('/^\d{2}-\d{2}$/D', $value)) {
            return false;
        }
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', '2024-' . $value);
        return $date && $date->format('m-d') === $value;
    }
}
