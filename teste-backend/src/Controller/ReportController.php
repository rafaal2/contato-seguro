<?php

namespace Contatoseguro\TesteBackend\Controller;

use Contatoseguro\TesteBackend\Service\CompanyService;
use Contatoseguro\TesteBackend\Service\ProductService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class ReportController
{
    private ProductService $productService;
    private CompanyService $companyService;

    public function __construct()
    {
        $this->productService = new ProductService();
        $this->companyService = new CompanyService();
    }

    public function generate(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $adminUserId = $request->getHeader('admin_user_id')[0];

        $data = [];
        $data[] = [
            'Id do produto',
            'Nome da Empresa',
            'Nome do Produto',
            'Valor do Produto',
            'Categoria do Produto',
            'Data de Criação',
            'Última Alteração de Preço',
            'Logs de Alterações'
        ];

        $stm = $this->productService->getAll($adminUserId);
        $products = $stm->fetchAll();

        foreach ($products as $i => $product) {
            $stm = $this->companyService->getNameById($product->company_id);
            $companyName = $stm->fetch()->name ?? 'Empresa não encontrada';

            $stm = $this->productService->getLog($product->id);
            $productLogs = $stm->fetchAll();

            $formattedLogs = [];
            foreach ($productLogs as $log) {
                $formattedLogs[] = sprintf(
                    "(%s, %s, %s)",
                    $log->admin_user_name,
                    ucfirst($log->action),
                    date('d/m/Y H:i:s', strtotime($log->timestamp))
                );
            }

            $lastPriceChange = $this->productService->getLastPriceChange($product->id);
            $lastPriceChangeInfo = $lastPriceChange
                ? sprintf(
                    "%s (%s)",
                    $lastPriceChange['admin_user_name'],
                    date('d/m/Y H:i:s', strtotime($lastPriceChange['timestamp']))
                )
                : 'Nenhuma alteração';

            $category = !empty($product->category) ? $product->category : 'Sem categoria';

            $data[$i + 1] = [
                $product->id,
                $companyName,
                $product->title,
                $product->price,
                $category,
                $product->created_at,
                $lastPriceChangeInfo,
                implode(', ', $formattedLogs)
            ];
        }

        $report = "<table style='font-size: 10px;'>";
        foreach ($data as $row) {
            $report .= "<tr>";
            foreach ($row as $column) {
                $report .= "<td style='padding: 5px;'>{$column}</td>";
            }
            $report .= "</tr>";
        }
        $report .= "</table>";

        $response->getBody()->write($report);
        return $response->withStatus(200)->withHeader('Content-Type', 'text/html');
    }
}
