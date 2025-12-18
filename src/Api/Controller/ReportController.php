<?php

declare(strict_types=1);

namespace Api\Controller;

use Api\Response\JsonResponse;
use Domain\Company\ValueObject\CompanyId;
use Domain\Reporting\Entity\Report;
use Domain\Reporting\Repository\ReportRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Report controller for financial reports.
 */
final class ReportController
{
    public function __construct(
        private readonly ReportRepositoryInterface $reportRepository
    ) {
    }

    /**
     * GET /api/v1/companies/{companyId}/reports
     */
    public function list(ServerRequestInterface $request): ResponseInterface
    {
        $companyId = $request->getAttribute('companyId');
        if ($companyId === null) {
            return JsonResponse::error('Company ID required', 400);
        }

        try {
            $reports = $this->reportRepository->findByCompany(
                CompanyId::fromString($companyId)
            );

            $data = array_map(fn(Report $r) => $this->formatReport($r, false), $reports);

            return JsonResponse::success($data);
        } catch (\Throwable $e) {
            return JsonResponse::error($e->getMessage(), 500);
        }
    }

    /**
     * GET /api/v1/companies/{companyId}/reports/{id}
     */
    public function get(ServerRequestInterface $request): ResponseInterface
    {
        $id = $request->getAttribute('id');
        if ($id === null) {
            return JsonResponse::error('Report ID required', 400);
        }

        try {
            $report = $this->reportRepository->findById(
                \Domain\Reporting\ValueObject\ReportId::fromString($id)
            );

            if ($report === null) {
                return JsonResponse::error('Report not found', 404);
            }

            return JsonResponse::success($this->formatReport($report, true));
        } catch (\Throwable $e) {
            return JsonResponse::error($e->getMessage(), 500);
        }
    }

    /**
     * Format report for API response.
     * 
     * @param bool $includeData Whether to include full report data
     */
    private function formatReport(Report $report, bool $includeData): array
    {
        $formatted = [
            'id' => $report->id()->toString(),
            'company_id' => $report->companyId()->toString(),
            'type' => $report->type(),
            'period' => [
                'start' => $report->period()->startDate()->format('Y-m-d'),
                'end' => $report->period()->endDate()->format('Y-m-d'),
                'type' => $report->period()->type()->value,
            ],
            'generated_at' => $report->generatedAt()->format('Y-m-d\TH:i:s\Z'),
        ];

        if ($includeData) {
            $formatted['data'] = $report->data();
        }

        return $formatted;
    }
}
