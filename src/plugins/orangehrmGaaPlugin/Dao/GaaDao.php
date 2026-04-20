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

namespace OrangeHRM\Gaa\Dao;

use DateTime;
use OrangeHRM\Core\Dao\BaseDao;
use OrangeHRM\Entity\Employee;
use OrangeHRM\Entity\GaaCatalogo;
use OrangeHRM\Entity\GaaHistorico;
use OrangeHRM\Entity\GaaSolicitacao;
use OrangeHRM\Entity\GaaSolicitacaoItem;
use OrangeHRM\Entity\User;
use OrangeHRM\Gaa\Dto\GaaCatalogoSearchFilterParams;
use OrangeHRM\Gaa\Dto\GaaSolicitacaoSearchFilterParams;
use OrangeHRM\ORM\Paginator;

class GaaDao extends BaseDao
{
    public function criarSolicitacaoAdmissao(int $empNumber, ?int $liderEmpNumber = null): GaaSolicitacao
    {
        return $this->criarSolicitacao($empNumber, GaaSolicitacao::TIPO_ADMISSAO, $liderEmpNumber);
    }

    public function criarSolicitacaoDesligamento(int $empNumber, ?int $liderEmpNumber = null): GaaSolicitacao
    {
        return $this->criarSolicitacao($empNumber, GaaSolicitacao::TIPO_DESLIGAMENTO, $liderEmpNumber);
    }

    private function criarSolicitacao(int $empNumber, string $tipo, ?int $liderEmpNumber): GaaSolicitacao
    {
        $solicitacao = new GaaSolicitacao();
        $employee = $this->getRepository(Employee::class)->find($empNumber);
        if ($employee === null) {
            throw new \RuntimeException("Employee {$empNumber} não encontrado para criar solicitação GAA");
        }
        $solicitacao->setEmployee($employee);
        $solicitacao->setTipo($tipo);
        $solicitacao->setStatus(GaaSolicitacao::STATUS_PENDENTE_LIDER);
        $now = new DateTime();
        $solicitacao->setCriadoEm($now);
        $solicitacao->setAtualizadoEm($now);

        if ($liderEmpNumber !== null) {
            $lider = $this->getRepository(Employee::class)->find($liderEmpNumber);
            if ($lider !== null) {
                $solicitacao->setLider($lider);
            }
        }

        $this->persist($solicitacao);
        return $solicitacao;
    }

    public function getSolicitacaoById(int $id): ?GaaSolicitacao
    {
        return $this->getRepository(GaaSolicitacao::class)->find($id);
    }

    public function saveSolicitacao(GaaSolicitacao $solicitacao): GaaSolicitacao
    {
        $solicitacao->setAtualizadoEm(new DateTime());
        $this->persist($solicitacao);
        return $solicitacao;
    }

    public function getSolicitacaoList(GaaSolicitacaoSearchFilterParams $params): array
    {
        return $this->getSolicitacaoPaginator($params)->getQuery()->execute();
    }

    public function getSolicitacaoCount(GaaSolicitacaoSearchFilterParams $params): int
    {
        return $this->getSolicitacaoPaginator($params)->count();
    }

    private function getSolicitacaoPaginator(GaaSolicitacaoSearchFilterParams $params): Paginator
    {
        $qb = $this->createQueryBuilder(GaaSolicitacao::class, 's');
        $qb->leftJoin('s.employee', 'emp');

        if ($params->getEmpNumber() !== null) {
            $qb->andWhere('emp.empNumber = :emp')->setParameter('emp', $params->getEmpNumber());
        }
        if ($params->getLiderEmpNumber() !== null) {
            $qb->leftJoin('s.lider', 'lid');
            $qb->andWhere('lid.empNumber = :lid')->setParameter('lid', $params->getLiderEmpNumber());
        }
        if ($params->getTipo() !== null) {
            $qb->andWhere('s.tipo = :tipo')->setParameter('tipo', $params->getTipo());
        }
        if ($params->getStatus() !== null) {
            $qb->andWhere('s.status = :status')->setParameter('status', $params->getStatus());
        }

        $this->setSortingAndPaginationParams($qb, $params);
        return $this->getPaginator($qb);
    }

    public function getItensBySolicitacao(int $solicitacaoId): array
    {
        return $this->getRepository(GaaSolicitacaoItem::class)
            ->findBy(['solicitacao' => $solicitacaoId]);
    }

    public function getItemById(int $id): ?GaaSolicitacaoItem
    {
        return $this->getRepository(GaaSolicitacaoItem::class)->find($id);
    }

    public function saveItem(GaaSolicitacaoItem $item): GaaSolicitacaoItem
    {
        $this->persist($item);
        return $item;
    }

    public function deleteItem(int $id): bool
    {
        $item = $this->getItemById($id);
        if ($item === null) {
            return false;
        }
        $this->remove($item);
        return true;
    }

    public function getItensPendentesRevisaoTi(): array
    {
        return $this->getRepository(GaaSolicitacaoItem::class)
            ->findBy(['status' => GaaSolicitacaoItem::STATUS_PENDENTE_TI_REVISAO]);
    }

    public function getCatalogoById(int $id): ?GaaCatalogo
    {
        return $this->getRepository(GaaCatalogo::class)->find($id);
    }

    public function saveCatalogo(GaaCatalogo $catalogo): GaaCatalogo
    {
        $this->persist($catalogo);
        return $catalogo;
    }

    public function deleteCatalogo(array $ids): int
    {
        $qb = $this->createQueryBuilder(GaaCatalogo::class, 'c');
        $qb->update()->set('c.ativo', '0')
            ->where($qb->expr()->in('c.id', ':ids'))
            ->setParameter('ids', $ids);
        return $qb->getQuery()->execute();
    }

    public function getCatalogoList(GaaCatalogoSearchFilterParams $params): array
    {
        return $this->getCatalogoPaginator($params)->getQuery()->execute();
    }

    public function getCatalogoCount(GaaCatalogoSearchFilterParams $params): int
    {
        return $this->getCatalogoPaginator($params)->count();
    }

    private function getCatalogoPaginator(GaaCatalogoSearchFilterParams $params): Paginator
    {
        $qb = $this->createQueryBuilder(GaaCatalogo::class, 'c');
        if ($params->getTipoItem() !== null) {
            $qb->andWhere('c.tipoItem = :tipo')->setParameter('tipo', $params->getTipoItem());
        }
        if ($params->getNome() !== null) {
            $qb->andWhere('c.nome LIKE :nome')->setParameter('nome', '%' . $params->getNome() . '%');
        }
        if ($params->getAtivo() !== null) {
            $qb->andWhere('c.ativo = :ativo')->setParameter('ativo', $params->getAtivo());
        }
        $this->setSortingAndPaginationParams($qb, $params);
        return $this->getPaginator($qb);
    }

    public function getHistoricoByEmpNumber(int $empNumber): array
    {
        $qb = $this->createQueryBuilder(GaaHistorico::class, 'h');
        $qb->leftJoin('h.item', 'i')
            ->leftJoin('i.solicitacao', 's')
            ->leftJoin('s.employee', 'e')
            ->where('e.empNumber = :emp')
            ->setParameter('emp', $empNumber)
            ->orderBy('h.criadoEm', 'DESC');
        return $qb->getQuery()->execute();
    }

    public function registrarHistorico(
        GaaSolicitacaoItem $item,
        ?User $user,
        string $acao,
        ?string $comentario = null,
        ?array $payload = null
    ): GaaHistorico {
        $historico = new GaaHistorico();
        $historico->setItem($item);
        $historico->setUser($user);
        $historico->setAcao($acao);
        $historico->setComentario($comentario);
        if ($payload !== null) {
            $encoded = json_encode($payload);
            $historico->setPayloadJson($encoded === false ? null : $encoded);
        } else {
            $historico->setPayloadJson(null);
        }
        $historico->setCriadoEm(new DateTime());
        $this->persist($historico);
        return $historico;
    }

    public function getMinhasPendenciasComoLider(int $empNumber): array
    {
        $qb = $this->createQueryBuilder(GaaSolicitacao::class, 's');
        $qb->leftJoin('s.lider', 'l')
            ->where('l.empNumber = :emp')
            ->andWhere('s.status = :status')
            ->setParameter('emp', $empNumber)
            ->setParameter('status', GaaSolicitacao::STATUS_PENDENTE_LIDER)
            ->orderBy('s.criadoEm', 'DESC');
        return $qb->getQuery()->execute();
    }

    public function getMinhasPendenciasComoTi(): array
    {
        return $this->getRepository(GaaSolicitacao::class)
            ->findBy(['status' => GaaSolicitacao::STATUS_PENDENTE_TI], ['criadoEm' => 'DESC']);
    }
}
