<?php

namespace Tests\Unit;

use App\Constants\ReportStatus;
use App\Models\Report;
use App\Models\Status;
use App\Models\SubStatus;
use App\Models\User;
use App\Services\ReportService;
use App\Services\ReportStatusTransitionService;
use Illuminate\Container\Container;
use Illuminate\Support\Facades\Facade;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Factory as ValidationFactory;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\TestCase;

class ReportStatusTransitionServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $container = new Container();
        $translator = new Translator(new ArrayLoader(), 'en');
        $validatorFactory = new ValidationFactory($translator, $container);

        $container->instance('translator', $translator);
        $container->instance('validator', $validatorFactory);

        Container::setInstance($container);
        Facade::setFacadeApplication($container);
    }

    protected function tearDown(): void
    {
        Facade::setFacadeApplication(null);
        Container::setInstance(null);

        parent::tearDown();
    }

    public function test_it_changes_status_when_target_is_different(): void
    {
        $reportService = $this->createMock(ReportService::class);
        $service = new ReportStatusTransitionService($reportService);

        $currentStatus = $this->makeStatus(1, ReportStatus::REPORTED_TO_DAMARISK);
        $targetStatus = $this->makeStatus(2, ReportStatus::WAITING_FOR_INSURER_DAMAGE_ID);
        $report = $this->makeReport($currentStatus);
        $user = $this->makeUser(10);
        $expectedReport = new Report();

        $reportService->expects($this->once())
            ->method('changeReportStatus')
            ->with(
                $report,
                $targetStatus->id,
                null,
                [
                    'user_id' => $user->id,
                    'comment' => 'Switching status',
                ]
            )
            ->willReturn($expectedReport);

        $result = $service->transition($report, $targetStatus, null, $user, ['comment' => 'Switching status']);

        $this->assertSame($expectedReport, $result);
    }

    public function test_it_allows_repeating_under_insurer_administration_without_substatus(): void
    {
        $reportService = $this->createMock(ReportService::class);
        $service = new ReportStatusTransitionService($reportService);

        $status = $this->makeStatus(5, ReportStatus::UNDER_INSURER_ADMINISTRATION);
        $report = $this->makeReport($status);
        $user = $this->makeUser(20);
        $expectedReport = new Report();

        $reportService->expects($this->once())
            ->method('changeReportStatus')
            ->with(
                $report,
                $status->id,
                null,
                [
                    'user_id' => $user->id,
                    'comment' => null,
                ]
            )
            ->willReturn($expectedReport);

        $result = $service->transition($report, $status, null, $user);

        $this->assertSame($expectedReport, $result);
    }

    public function test_it_prevents_repeating_non_repeatable_status(): void
    {
        $reportService = $this->createMock(ReportService::class);
        $service = new ReportStatusTransitionService($reportService);

        $status = $this->makeStatus(3, ReportStatus::REPORTED_TO_DAMARISK);
        $report = $this->makeReport($status);
        $user = $this->makeUser(30);

        $reportService->expects($this->never())->method('changeReportStatus');

        $this->expectException(ValidationException::class);

        $service->transition($report, $status, null, $user);
    }

    private function makeStatus(int $id, string $name): Status
    {
        $status = new Status();
        $status->id = $id;
        $status->name = $name;

        return $status;
    }

    private function makeReport(Status $status, ?SubStatus $subStatus = null): Report
    {
        $report = new Report();
        $report->status_id = $status->id;
        $report->sub_status_id = $subStatus?->id;
        $report->setRelation('status', $status);
        $report->setRelation('subStatus', $subStatus);

        return $report;
    }

    private function makeUser(int $id): User
    {
        $user = new User();
        $user->id = $id;

        return $user;
    }
}
