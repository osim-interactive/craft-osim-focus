<?php
namespace osim\craft\focus\migrations;

use Craft;
use craft\db\Migration;
use craft\helpers\MigrationHelper;
use osim\craft\focus\Plugin;
use osim\craft\focus\elements\Issue as IssueElement;
use osim\craft\focus\elements\Page as PageElement;

class Install extends Migration
{
    public function safeUp()
    {
        $this->createTable(
            '{{%osim_focus_accounts}}',
            [
                'id' => $this->primaryKey(),
                'name' => $this->string(250)->notNull(),
                'osimFocusApiKey' => $this->string(250)->notNull(),
                'certainty' => $this->integer(),
                'priority' => $this->integer(),
                'wcag' => $this->boolean(),
                'wcagLevel' => $this->string(3),
                'bestPractice' => $this->boolean(),
                'store' => $this->boolean(),
                'userAgent' => $this->string(250),
                'delay' => $this->integer(),
                'uid' => $this->uid(),
            ]
        );

        $this->createTable(
            '{{%osim_focus_viewports}}',
            [
                'id' => $this->primaryKey(),
                'accountId' => $this->integer(),
                'name' => $this->string(250)->notNull(),
                'width' => $this->integer()->notNull(),
                'height' => $this->integer()->notNull(),
                'uid' => $this->uid(),
            ]
        );
        $this->createIndex('accountId', '{{%osim_focus_viewports}}', 'accountId');

        $this->createTable(
            '{{%osim_focus_projects}}',
            [
                'id' => $this->primaryKey(),
                'name' => $this->string(50),
                'siteId' => $this->integer()->notNull(),
                'accountId' => $this->integer()->notNull(),
                'osimFocusProjectId' => $this->string(250),
                'sitemapUrl' => $this->string(250)->notNull(),
                'certainty' => $this->integer(),
                'priority' => $this->integer(),
                'wcag' => $this->boolean(),
                'wcagLevel' => $this->string(3),
                'bestPractice' => $this->boolean(),
                'store' => $this->boolean(),
                'userAgent' => $this->string(250),
                'delay' => $this->integer(),
                'uid' => $this->uid(),
            ]
        );
        $this->createIndex('siteId', '{{%osim_focus_projects}}', 'siteId');
        $this->createIndex('accountId', '{{%osim_focus_projects}}', 'accountId');

        $this->createTable(
            '{{%osim_focus_projects_viewports}}',
            [
                'id' => $this->primaryKey(),
                'projectId' => $this->integer()->notNull(),
                'viewportId' => $this->integer()->notNull(),
            ]
        );
        $this->createIndex('projectId', '{{%osim_focus_projects_viewports}}', 'projectId');
        $this->createIndex('viewportId', '{{%osim_focus_projects_viewports}}', 'viewportId');

        $this->createTable(
            '{{%osim_focus_pages}}',
            [
                'id' => $this->primaryKey(),
                'projectId' => $this->integer()->notNull(),
                'pageTitle' => $this->string(250)->notNull(),
                'pageUrl' => $this->string(250)->notNull(),
                'wcagAIssues' => $this->integer()->notNull(),
                'wcagAaIssues' => $this->integer()->notNull(),
                'wcagAaaIssues' => $this->integer()->notNull(),
                'bestPracticeIssues' => $this->integer()->notNull(),
                'totalIssues' => $this->integer()->notNull(),
                'uid' => $this->uid(),
            ]
        );
        $this->createIndex('projectId', '{{%osim_focus_pages}}', 'projectId');
        $this->createIndex('pageUrl', '{{%osim_focus_pages}}', ['projectId', 'pageUrl'], true);
        $this->createIndex('wcagAIssues', '{{%osim_focus_pages}}', 'wcagAIssues');
        $this->createIndex('wcagAaIssues', '{{%osim_focus_pages}}', 'wcagAaIssues');
        $this->createIndex('wcagAaaIssues', '{{%osim_focus_pages}}', 'wcagAaaIssues');
        $this->createIndex('bestPracticeIssues', '{{%osim_focus_pages}}', 'bestPracticeIssues');
        $this->createIndex('totalIssues', '{{%osim_focus_pages}}', 'totalIssues');

        $this->createTable(
            '{{%osim_focus_issues}}',
            [
                'id' => $this->primaryKey(),
                'pageId' => $this->integer()->notNull(),
                'viewportId' => $this->integer()->notNull(),
                'certainty' => $this->integer()->notNull(),
                'priority' => $this->integer()->notNull(),
                'ruleId' => $this->integer()->notNull(),
                'ruleName' => $this->string(250)->notNull(),
                'ruleDescription' => $this->text(),
                'snippet' => $this->text()->notNull(),
                'xpath' => $this->text()->notNull(),
                'selector' => $this->text()->notNull(),
                'wcag' => $this->boolean()->notNull()->defaultValue(false),
                'wcagLevel' => $this->string(3),
                'bestPractice' => $this->boolean()->notNull()->defaultValue(false),
                'summary' => $this->text(),
                'resolved' => $this->boolean()->notNull()->defaultValue(false),
                'uid' => $this->uid(),
            ]
        );
        $this->createIndex('pageId', '{{%osim_focus_issues}}', 'pageId');
        $this->createIndex('viewportId', '{{%osim_focus_issues}}', 'viewportId');
        $this->createIndex('priority', '{{%osim_focus_issues}}', 'priority');
        $this->createIndex('certainty', '{{%osim_focus_issues}}', 'certainty');
        $this->createIndex('ruleId', '{{%osim_focus_issues}}', 'ruleId');
        $this->createIndex('ruleName', '{{%osim_focus_issues}}', 'ruleName');
        $this->createIndex('wcag', '{{%osim_focus_issues}}', 'wcag');
        $this->createIndex('wcagLevel', '{{%osim_focus_issues}}', 'wcagLevel');
        $this->createIndex('bestPractice', '{{%osim_focus_issues}}', 'bestPractice');
        $this->createIndex('resolved', '{{%osim_focus_issues}}', 'resolved');

        $this->createTable(
            '{{%osim_focus_ignore_rules}}',
            [
                'id' => $this->primaryKey(),
                'name' => $this->string(250)->notNull(),
                'accountId' => $this->integer(),
                'projectId' => $this->integer(),
                'viewportId' => $this->integer(),
                'pageUrlComparator' => $this->string(32),
                'pageUrlValue' => $this->string(250),
                'ruleId' => $this->integer(),
                'xpathComparator' => $this->string(32),
                'xpathValue' => $this->text(),
                'selectorComparator' => $this->string(32),
                'selectorValue' => $this->text(),
                'uid' => $this->uid(),
            ]
        );
        $this->createIndex('accountId', '{{%osim_focus_ignore_rules}}', 'accountId');
        $this->createIndex('projectId', '{{%osim_focus_ignore_rules}}', 'projectId');
        $this->createIndex('viewportId', '{{%osim_focus_ignore_rules}}', 'viewportId');

        $this->createTable(
            '{{%osim_focus_history}}',
            [
                'id' => $this->primaryKey(),
                'projectId' => $this->integer()->notNull(),
                'viewportId' => $this->integer(),
                'dateJob' => $this->dateTime(),
                'dateLast' => $this->dateTime(),
                'status' => $this->integer(),
            ]
        );
        $this->createIndex('projectId', '{{%osim_focus_history}}', 'projectId');
        $this->createIndex('viewportId', '{{%osim_focus_history}}', 'viewportId');

        // Viewports
        $this->addForeignKey(
            'osim_focus_viewports_account_id',
            '{{%osim_focus_viewports}}',
            'accountId',
            '{{%osim_focus_accounts}}',
            'id',
            'CASCADE'
        );

        // Projects
        $this->addForeignKey(
            null,
            '{{%osim_focus_projects}}',
            'siteId',
            '{{%sites}}',
            'id',
            'CASCADE',
            'CASCADE'
        );

        $this->addForeignKey(
            null,
            '{{%osim_focus_projects}}',
            'accountId',
            '{{%osim_focus_accounts}}',
            'id',
            'CASCADE'
        );

        // Projects Viewports
        $this->addForeignKey(
            null,
            '{{%osim_focus_projects_viewports}}',
            'projectId',
            '{{%osim_focus_projects}}',
            'id',
            'CASCADE'
        );

        $this->addForeignKey(
            null,
            '{{%osim_focus_projects_viewports}}',
            'viewportId',
            '{{%osim_focus_viewports}}',
            'id',
            'CASCADE'
        );

        // Pages
        $this->addForeignKey(
            null,
            '{{%osim_focus_pages}}',
            'id',
            '{{%elements}}',
            'id',
            'CASCADE'
        );
        $this->addForeignKey(
            null,
            '{{%osim_focus_pages}}',
            'projectId',
            '{{%osim_focus_projects}}',
            'id',
            'CASCADE'
        );

        // Issues
        $this->addForeignKey(
            null,
            '{{%osim_focus_issues}}',
            'id',
            '{{%elements}}',
            'id',
            'CASCADE'
        );
        $this->addForeignKey(
            null,
            '{{%osim_focus_issues}}',
            'pageId',
            '{{%osim_focus_pages}}',
            'id',
            'CASCADE'
        );

        $this->addForeignKey(
            null,
            '{{%osim_focus_issues}}',
            'viewportId',
            '{{%osim_focus_viewports}}',
            'id',
            'CASCADE'
        );

        // Ignore rules
        $this->addForeignKey(
            null,
            '{{%osim_focus_ignore_rules}}',
            'accountId',
            '{{%osim_focus_accounts}}',
            'id',
            'CASCADE'
        );

        $this->addForeignKey(
            null,
            '{{%osim_focus_ignore_rules}}',
            'projectId',
            '{{%osim_focus_projects}}',
            'id',
            'CASCADE'
        );

        $this->addForeignKey(
            null,
            '{{%osim_focus_ignore_rules}}',
            'viewportId',
            '{{%osim_focus_viewports}}',
            'id',
            'CASCADE'
        );

        // History
        $this->addForeignKey(
            null,
            '{{%osim_focus_history}}',
            'projectId',
            '{{%osim_focus_projects}}',
            'id',
            'CASCADE'
        );

        $this->addForeignKey(
            null,
            '{{%osim_focus_history}}',
            'viewportId',
            '{{%osim_focus_viewports}}',
            'id',
            'CASCADE'
        );
    }

    public function safeDown()
    {
        $tables = [
            '{{%osim_focus_accounts}}',
            '{{%osim_focus_viewports}}',
            '{{%osim_focus_projects}}',
            '{{%osim_focus_projects_viewports}}',
            '{{%osim_focus_pages}}',
            '{{%osim_focus_issues}}',
            '{{%osim_focus_ignore_rules}}',
            '{{%osim_focus_history}}',
        ];

        foreach ($tables as $table) {
            if (!$this->db->tableExists($table)) {
                continue;
            }

            MigrationHelper::dropTable($table, $this);
        }

        // Remove elements rows
        $this->delete('{{%elements}}', ['type' => PageElement::class]);
        $this->delete('{{%elements}}', ['type' => IssueElement::class]);

        // Remove project config
        Craft::$app->projectConfig->remove(Plugin::PROJECT_CONFIG_PATH);
    }
}
