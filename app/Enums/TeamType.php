<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Represents the type of a Team entity.
 *
 * Hierarchical types (line management): Enterprise → Sector → Organisation → BusinessUnit → Division → Department → Unit
 * Cross-functional types (floating): Discipline, Group, Squad, Project
 */
enum TeamType: string
{
    // Hierarchical (Line Management) - Levels 1-7
    case ENTERPRISE = 'enterprise';       // Level 1
    case SECTOR = 'sector';               // Level 2
    case ORGANISATION = 'organisation';   // Level 3
    case BUSINESS_UNIT = 'business_unit'; // Level 4
    case DIVISION = 'division';           // Level 5
    case DEPARTMENT = 'department';       // Level 6
    case UNIT = 'unit';                   // Level 7

    // Cross-Functional (Floating) - No hierarchy
    case DISCIPLINE = 'discipline';
    case GROUP = 'group';
    case SQUAD = 'squad';
    case PROJECT = 'project';

    /**
     * Get human-readable label for this team type.
     */
    public function label(): string
    {
        return match ($this) {
            self::ENTERPRISE => 'Enterprise',
            self::SECTOR => 'Sector',
            self::ORGANISATION => 'Organisation',
            self::BUSINESS_UNIT => 'Business Unit',
            self::DIVISION => 'Division',
            self::DEPARTMENT => 'Department',
            self::UNIT => 'Unit',
            self::DISCIPLINE => 'Discipline',
            self::GROUP => 'Group',
            self::SQUAD => 'Squad',
            self::PROJECT => 'Project',
        };
    }

    /**
     * Get the organizational mode for this team type.
     */
    public function mode(): TeamMode
    {
        return match ($this) {
            self::ENTERPRISE,
            self::SECTOR,
            self::ORGANISATION,
            self::BUSINESS_UNIT,
            self::DIVISION,
            self::DEPARTMENT,
            self::UNIT => TeamMode::HIERARCHICAL,

            self::DISCIPLINE,
            self::GROUP,
            self::SQUAD,
            self::PROJECT => TeamMode::CROSS_FUNCTIONAL,
        };
    }

    /**
     * Get the hierarchy level for hierarchical types (1-7).
     * Returns null for cross-functional (floating) types.
     */
    public function level(): ?int
    {
        return match ($this) {
            self::ENTERPRISE => 1,
            self::SECTOR => 2,
            self::ORGANISATION => 3,
            self::BUSINESS_UNIT => 4,
            self::DIVISION => 5,
            self::DEPARTMENT => 6,
            self::UNIT => 7,
            default => null, // Cross-functional types have no level
        };
    }

    /**
     * Check if this team type is floating (cross-functional, no parent required).
     */
    public function isFloating(): bool
    {
        return $this->mode() === TeamMode::CROSS_FUNCTIONAL;
    }

    /**
     * Check if this team type is hierarchical.
     */
    public function isHierarchical(): bool
    {
        return $this->mode() === TeamMode::HIERARCHICAL;
    }

    /**
     * Get the scope description for this team type.
     */
    public function scopeDescription(): string
    {
        return match ($this) {
            self::ENTERPRISE => 'Global Holdings',
            self::SECTOR => 'Industry Vertical',
            self::ORGANISATION => 'Operating Company',
            self::BUSINESS_UNIT => 'Strategic Profit Center',
            self::DIVISION => 'Major Functional Block',
            self::DEPARTMENT => 'Administrative Budget Holder',
            self::UNIT => 'Operational Team',
            self::DISCIPLINE => 'Area of Competency',
            self::GROUP => 'Feature/Topic Cluster',
            self::SQUAD => 'Cross-functional Execution Team',
            self::PROJECT => 'Time-bound Initiative',
        };
    }
}
