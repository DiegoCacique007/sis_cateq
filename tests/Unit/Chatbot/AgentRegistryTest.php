<?php

namespace Tests\Unit\Chatbot;

use App\Enums\ChatbotIntent as I;
use App\Enums\UserRole as R;
use App\Services\Authorization\AccessContext;
use App\Services\Chatbot\Agents\AdministrationAgent;
use App\Services\Chatbot\Agents\AgentRequest;
use App\Services\Chatbot\Agents\EscalationAgent;
use App\Services\Chatbot\Agents\EvaluationsAgent;
use App\Services\Chatbot\Agents\GroupsAgent;
use App\Services\Chatbot\Agents\HelpAgent;
use App\Services\Chatbot\Agents\StudentsAgent;
use App\Services\Chatbot\Routing\AgentRegistry;
use App\Services\Chatbot\Rules\ChatbotFacts;
use App\Services\Chatbot\Rules\RuleEngine;
use Illuminate\Container\Container;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class AgentRegistryTest extends TestCase
{
    public static function routing(): array
    {
        return [
            [I::ViewMyGroups, R::Catequista, GroupsAgent::class],
            [I::ViewGroupStudents, R::Catequista, StudentsAgent::class],
            [I::ViewAttendanceList, R::Catequista, GroupsAgent::class],
            [I::ViewEvaluations, R::Catequista, EvaluationsAgent::class],
            [I::HelpSystem, R::Catequista, HelpAgent::class],
            [I::HowToRegisterStudent, R::Secretaria, AdministrationAgent::class],
            [I::HowToRecordEvaluations, R::Catequista, AdministrationAgent::class],
            [I::ModifyAdministrativeData, R::Catequista, EscalationAgent::class],
            [I::ModifyStudent, R::Catequista, EscalationAgent::class],
            [I::Unknown, R::Secretaria, EscalationAgent::class],
            [I::HowToManageUsers, R::Parroco, EscalationAgent::class],
        ];
    }

    #[DataProvider('routing')]
    public function test_routing_without_database(I $intent, R $role, string $expected): void
    {
        $container = new Container;
        $facts = new ChatbotFacts(new AccessContext(1, $role, 'aprobado', 1, 1), $intent);
        $request = AgentRequest::evaluate($facts, $container->make(RuleEngine::class));
        $this->assertInstanceOf($expected, $container->make(AgentRegistry::class)->resolve($request));
    }

    public function test_missing_period_never_routes_to_data_agent(): void
    {
        $container = new Container;
        $facts = new ChatbotFacts(new AccessContext(1, R::Catequista, 'aprobado', 1, null), I::ViewGroupStudents);
        $request = AgentRequest::evaluate($facts, $container->make(RuleEngine::class));
        $this->assertInstanceOf(EscalationAgent::class, $container->make(AgentRegistry::class)->resolve($request));
    }

    public function test_request_cannot_accept_arbitrary_rule_results_and_is_immutable(): void
    {
        $this->assertTrue((new \ReflectionClass(AgentRequest::class))->getConstructor()->isPrivate());
        $this->assertTrue((new \ReflectionClass(AgentRequest::class))->isReadOnly());
    }
}
