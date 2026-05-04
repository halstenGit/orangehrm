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

namespace OrangeHRM\Gaa\Subscriber;

use OrangeHRM\Core\Traits\LoggerTrait;
use OrangeHRM\Framework\Event\AbstractEventSubscriber;
use OrangeHRM\Gaa\Event\CandidateHiredEvent;
use OrangeHRM\Gaa\Event\EmployeeTerminatedEvent;
use OrangeHRM\Gaa\Event\GaaEvents;
use OrangeHRM\Gaa\Traits\Service\GaaServiceTrait;
use Throwable;

class GaaEventSubscriber extends AbstractEventSubscriber
{
    use GaaServiceTrait;
    use LoggerTrait;

    public static function getSubscribedEvents(): array
    {
        return [
            GaaEvents::CANDIDATE_HIRED => 'onCandidateHired',
            GaaEvents::EMPLOYEE_TERMINATED => 'onEmployeeTerminated',
        ];
    }

    public function onCandidateHired(CandidateHiredEvent $event): void
    {
        try {
            $this->getGaaService()->criarSolicitacaoAdmissao($event->getEmpNumber());
        } catch (Throwable $e) {
            // Never propagate: hire/terminate must not be aborted by GAA provisioning failures.
            $this->getLogger()->error(
                'GAA: falha ao criar solicitação de admissão para empNumber=' . $event->getEmpNumber()
                . ' — ' . $e->getMessage(),
                ['exception' => $e]
            );
        }
    }

    public function onEmployeeTerminated(EmployeeTerminatedEvent $event): void
    {
        try {
            $this->getGaaService()->criarSolicitacaoDesligamento($event->getEmpNumber());
        } catch (Throwable $e) {
            $this->getLogger()->error(
                'GAA: falha ao criar solicitação de desligamento para empNumber=' . $event->getEmpNumber()
                . ' — ' . $e->getMessage(),
                ['exception' => $e]
            );
        }
    }
}
