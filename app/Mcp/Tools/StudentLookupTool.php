<?
namespace App\Mcp\Tools;

use App\Services\StudentService;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class StudentLookupTool extends Tool
{
    /**
     * The tool's name.
     */
    protected string $name = 'lookup_student';

    /**
     * The tool's description.
     */
    protected string $description = '透過姓名搜尋學校學生基本資料。';

    /**
     * Handle the tool execution.
     */
    public function handle(): Response
    {
        // 從目前的請求中取得參數 (MCP 套件會自動綁定參數)
        $name = request()->input('name');
        $schoolId = request()->input('school_id', 1);

        $service = app(StudentService::class);
        $students = $service->searchStudents($schoolId, $name);

        if ($students->isEmpty()) {
            return Response::text("找不到名為 {$name} 的學生。");
        }

        $result = $students->map(fn($s) => [
            'name' => $s->name,
            'class' => $s->class_full_name,
            'status' => $s->status->label(),
        ])->toArray();

        return Response::json($result);
    }
}
