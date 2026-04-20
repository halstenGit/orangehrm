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

namespace OrangeHRM\Gaa\Service;

use DateTime;
use OrangeHRM\Entity\GaaCatalogo;
use OrangeHRM\Entity\GaaHistorico;
use OrangeHRM\Entity\GaaSolicitacao;
use OrangeHRM\Entity\GaaSolicitacaoItem;
use OrangeHRM\Entity\User;
use OrangeHRM\Gaa\Dao\GaaDao;
use OrangeHRM\Pim\Service\EmployeeReportingMethodService;
use OrangeHRM\Pim\Dto\EmployeeSupervisorSearchFilterParams;

class GaaService
{
    private ?GaaDao $gaaDao = null;
    private ?EmployeeReportingMethodService $reportingService = null;

    public function getGaaDao(): GaaDao
    {
        return $this->gaaDao ??= new GaaDao();
    }

    public function getReportingService(): EmployeeReportingMethodService
    {
        return $this->reportingService ??= new EmployeeReportingMethodService();
    }

    public function criarSolicitacaoAdmissao(int $empNumber): GaaSolicitacao
    {
        $liderEmpNumber = $this->buscarLiderPrimario($empNumber);
        return $this->getGaaDao()->criarSolicitacaoAdmissao($empNumber, $liderEmpNumber);
    }

    public function criarSolicitacaoDesligamento(int $empNumber): GaaSolicitacao
    {
        $liderEmpNumber = $this->buscarLiderPrimario($empNumber);
        return $this->getGaaDao()->criarSolicitacaoDesligamento($empNumber, $liderEmpNumber);
    }

    private function buscarLiderPrimario(int $empNumber): ?int
    {
        $params = new EmployeeSupervisorSearchFilterParams();
        $params->setEmpNumber($empNumber);
        $supervisors = $this->getReportingService()->getImmediateSupervisorListForEmployee($params);
        if (empty($supervisors)) {
            return null;
        }
        $first = $supervisors[0];
        return $first->getSupervisor()->getEmpNumber();
    }

    public function avancarSolicitacaoParaTi(GaaSolicitacao $solicitacao): GaaSolicitacao
    {
        $solicitacao->setStatus(GaaSolicitacao::STATUS_PENDENTE_TI);
        return $this->getGaaDao()->saveSolicitacao($solicitacao);
    }

    public function concluirSolicitacao(GaaSolicitacao $solicitacao): GaaSolicitacao
    {
        $solicitacao->setStatus(GaaSolicitacao::STATUS_CONCLUIDA);
        $solicitacao->setConcluidoEm(new DateTime());
        return $this->getGaaDao()->saveSolicitacao($solicitacao);
    }

    public function aprovarItemAdhoc(GaaSolicitacaoItem $item, ?User $user): GaaSolicitacaoItem
    {
        $item->setStatus(GaaSolicitacaoItem::STATUS_APROVADO_ADHOC);
        $this->getGaaDao()->saveItem($item);
        $this->getGaaDao()->registrarHistorico($item, $user, GaaHistorico::ACAO_APROVADO_ADHOC);
        return $item;
    }

    public function promoverItemAoCatalogo(GaaSolicitacaoItem $item, ?User $user): GaaCatalogo
    {
        $catalogo = new GaaCatalogo();
        $catalogo->setNome($item->getLabelCustom() ?? 'Item sem nome');
        $catalogo->setTipoItem($item->getTipoItem());
        $catalogo->setQuantidadePadrao($item->getQuantidade());
        $catalogo->setAtivo(1);
        $catalogo->setCriadoDeSolicitacaoItem($item);
        $catalogo->setCriadoEm(new DateTime());
        $this->getGaaDao()->saveCatalogo($catalogo);

        $item->setCatalogo($catalogo);
        $item->setStatus(GaaSolicitacaoItem::STATUS_PROMOVIDO);
        $this->getGaaDao()->saveItem($item);

        $this->getGaaDao()->registrarHistorico(
            $item,
            $user,
            GaaHistorico::ACAO_PROMOVIDO_CATALOGO,
            null,
            ['catalogo_id' => $catalogo->getId()]
        );
        return $catalogo;
    }

    public function rejeitarItem(GaaSolicitacaoItem $item, ?User $user, string $motivo): GaaSolicitacaoItem
    {
        $item->setStatus(GaaSolicitacaoItem::STATUS_REJEITADO);
        $item->setMotivoRejeicao($motivo);
        $this->getGaaDao()->saveItem($item);
        $this->getGaaDao()->registrarHistorico($item, $user, GaaHistorico::ACAO_REJEITADO, $motivo);
        return $item;
    }

    public function concluirItem(GaaSolicitacaoItem $item, ?User $user): GaaSolicitacaoItem
    {
        $item->setStatus(GaaSolicitacaoItem::STATUS_CONCLUIDO);
        $this->getGaaDao()->saveItem($item);
        $this->getGaaDao()->registrarHistorico($item, $user, GaaHistorico::ACAO_CONCLUIDO);
        return $item;
    }
}
