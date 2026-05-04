<?php

/**
 * OrangeHRM is a comprehensive Human Resource Management (HRM) System that captures
 * all the essential functionalities required for any enterprise.
 * Copyright (C) 2006 OrangeHRM Inc., http://www.orangehrm.com
 *
 * OrangeHRM is free software: you can redistribute it and/or modify it under the terms of
 * the GNU General Public License as published by the Free Software Foundation, either
 * version 3 of the License, or (at your option) any later version.
 *
 * OrangeHRM is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
 * without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with OrangeHRM.
 * If not, see <https://www.gnu.org/licenses/>.
 */

namespace OrangeHRM\Admin\Api;

use OrangeHRM\Core\Api\V2\Endpoint;
use OrangeHRM\Core\Api\V2\EndpointCollectionResult;
use OrangeHRM\Core\Api\V2\EndpointResourceResult;
use OrangeHRM\Core\Api\V2\EndpointResult;
use OrangeHRM\Core\Api\V2\Exception\BadRequestException;
use OrangeHRM\Core\Api\V2\Model\ArrayModel;
use OrangeHRM\Core\Api\V2\RequestParams;
use OrangeHRM\Core\Api\V2\ResourceEndpoint;
use OrangeHRM\Core\Api\V2\Validator\ParamRule;
use OrangeHRM\Core\Api\V2\Validator\ParamRuleCollection;
use OrangeHRM\Core\Api\V2\Validator\Rule;
use OrangeHRM\Core\Api\V2\Validator\Rules;
use OrangeHRM\Core\Traits\ORM\EntityManagerHelperTrait;
use OrangeHRM\Entity\MenuItem;

/**
 * Allows admins to reorder/show/hide top-level and second-level menu items
 * and tweak their icon names without manual SQL.
 *
 * Endpoints:
 *   GET  /api/v2/admin/menu-items          → list all menu items (levels 1 & 2)
 *   PUT  /api/v2/admin/menu-items          → batch update [{id, orderHint, status, icon}]
 */
class MenuConfigAPI extends Endpoint implements ResourceEndpoint
{
    use EntityManagerHelperTrait;

    public const PARAMETER_ITEMS = 'items';
    public const PARAMETER_ID = 'id';
    public const PARAMETER_ORDER_HINT = 'orderHint';
    public const PARAMETER_STATUS = 'status';
    public const PARAMETER_ICON = 'icon';

    /**
     * Used as both the collection endpoint (GET) and the batch update (PUT).
     * Implements ResourceEndpoint so a single PUT body holds the diff for many rows.
     */
    public function getOne(): EndpointResult
    {
        $repo = $this->getEntityManager()->getRepository(MenuItem::class);
        $items = $repo->createQueryBuilder('m')
            ->leftJoin('m.parent', 'p')
            ->addSelect('p')
            ->where('m.level <= 2')
            ->orderBy('m.level', 'ASC')
            ->addOrderBy('m.orderHint', 'ASC')
            ->getQuery()
            ->getResult();

        $result = array_map(
            static function (MenuItem $item): array {
                $additional = $item->getAdditionalParams() ?? [];
                return [
                    'id' => $item->getId(),
                    'menuTitle' => $item->getMenuTitle(),
                    'level' => $item->getLevel(),
                    'orderHint' => $item->getOrderHint(),
                    'status' => $item->getStatus(),
                    'parentId' => $item->getParent()?->getId(),
                    'parentTitle' => $item->getParent()?->getMenuTitle(),
                    'icon' => $additional['icon'] ?? null,
                ];
            },
            $items
        );

        return new EndpointCollectionResult(ArrayModel::class, $result);
    }

    public function getValidationRuleForGetOne(): ParamRuleCollection
    {
        return new ParamRuleCollection();
    }

    public function update(): EndpointResult
    {
        $payload = $this->getRequestParams()->getArray(
            RequestParams::PARAM_TYPE_BODY,
            self::PARAMETER_ITEMS,
            []
        );
        if (empty($payload)) {
            throw new BadRequestException('Lista vazia: nada para atualizar.');
        }

        $repo = $this->getEntityManager()->getRepository(MenuItem::class);
        $em = $this->getEntityManager();
        $updated = [];

        foreach ($payload as $row) {
            if (!isset($row['id'])) {
                continue;
            }
            $item = $repo->find((int)$row['id']);
            if (!$item instanceof MenuItem) {
                continue;
            }
            // Limita ao mesmo escopo do GET — não deixa mexer em itens nivel 3+ por aqui.
            if ($item->getLevel() > 2) {
                continue;
            }

            if (array_key_exists('orderHint', $row) && $row['orderHint'] !== null) {
                $item->setOrderHint((int)$row['orderHint']);
            }
            if (array_key_exists('status', $row)) {
                $item->setStatus((bool)$row['status']);
            }
            if (array_key_exists('icon', $row)) {
                $additional = $item->getAdditionalParams() ?? [];
                $iconValue = is_string($row['icon']) ? trim($row['icon']) : '';
                if ($iconValue === '') {
                    unset($additional['icon']);
                } else {
                    $additional['icon'] = $iconValue;
                }
                $item->setAdditionalParams($additional ?: null);
            }
            $em->persist($item);
            $updated[] = $item->getId();
        }
        $em->flush();

        return new EndpointResourceResult(ArrayModel::class, ['updated' => $updated]);
    }

    public function getValidationRuleForUpdate(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            new ParamRule(self::PARAMETER_ITEMS, new Rule(Rules::ARRAY_TYPE))
        );
    }

    public function delete(): EndpointResult
    {
        throw $this->getNotImplementedException();
    }

    public function getValidationRuleForDelete(): ParamRuleCollection
    {
        throw $this->getNotImplementedException();
    }
}
