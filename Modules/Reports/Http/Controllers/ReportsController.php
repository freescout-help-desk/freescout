<?php

namespace Modules\Reports\Http\Controllers;

use App\Conversation;
use App\Customer;
use App\Thread;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

class ReportsController extends Controller
{
    public function conversationsReport()
    {
        if (!\Reports::canViewReports()) {
            \Helper::denyAccess();
        }
        $filters['to'] = User::dateFormat(date('Y-m-d H:i:s'), 'Y-m-d', null, false);
        $filters['from'] = date('Y-m-d', strtotime($filters['to'].' -1 week'));

        return view('reports::conversations', [
            'filters' => $filters
        ]);
    }

    public function productivityReport()
    {
        if (!\Reports::canViewReports()) {
            \Helper::denyAccess();
        }
        $filters['to'] = User::dateFormat(date('Y-m-d H:i:s'), 'Y-m-d', null, false);
        $filters['from'] = date('Y-m-d', strtotime($filters['to'].' -1 week'));

        return view('reports::productivity', [
            'filters' => $filters
        ]);
    }

    public function satisfactionReport()
    {
        if (!\Reports::canViewReports()) {
            \Helper::denyAccess();
        }
        $filters['to'] = User::dateFormat(date('Y-m-d H:i:s'), 'Y-m-d', null, false);
        $filters['from'] = date('Y-m-d', strtotime($filters['to'].' -1 week'));

        return view('reports::satisfaction', [
            'filters' => $filters
        ]);
    }

    public function timeReport()
    {
        if (!\Reports::canViewReports()) {
            \Helper::denyAccess();
        }
        $filters['to'] = User::dateFormat(date('Y-m-d H:i:s'), 'Y-m-d', null, false);
        $filters['from'] = date('Y-m-d', strtotime($filters['to'].' -1 week'));

        return view('reports::time', [
            'filters' => $filters
        ]);
    }

	/**
     * Ajax controller.
     */
    public function ajax(Request $request)
    {
        if (!\Reports::canViewReports()) {
            \Helper::denyAccess();
        }
        $response = [
            'status' => 'error',
            'msg'    => '', // this is error message
        ];

        $user = auth()->user();

        switch ($request->action) {

            case 'report':

                switch ($request->report_name) {
                    case \Reports::REPORT_CONVERSATIONS:
                        $data = $this->getReportDataConversations($request);
                        break;

                    case \Reports::REPORT_PRODUCTIVITY:
                        $data = $this->getReportDataProductivity($request);
                        break;

                    case \Reports::REPORT_SATISFACTION:
                        $data = $this->getReportSatisfaction($request);
                        break;

                    case \Reports::REPORT_TIME:
                        $data = $this->getReportTime($request);
                        break;
                }

                $response['report'] = view('reports::partials/report_'.$request->report_name, $data)->render();
                $response['chart'] = $data['chart'];
                $response['status'] = 'success';
                break;

            default:
                $response['msg'] = 'Unknown action';
                break;
        }

        if ($response['status'] == 'error' && empty($response['msg'])) {
            $response['msg'] = 'Unknown error occured';
        }

        return \Response::json($response);
    }

    public function getReportDataConversations($request)
    {
        $data = [];

        // Total Conversations.
        $value = $this->countTotalConv($request);
        $data['metrics']['total']['value'] = $value;
        $data['metrics']['total']['change'] = $this->calcChange($value, $this->countTotalConv($request, true));

        // New Conversations.
        $value = $this->countNewConv($request);
        $data['metrics']['new']['value'] = $value;
        $data['metrics']['new']['change'] = $this->calcChange($value, $this->countNewConv($request, true));

        // Messages received.
        $value = $this->countMessages($request);
        $data['metrics']['messages']['value'] = $value;
        $data['metrics']['messages']['change'] = $this->calcChange($value, $this->countMessages($request, true));

        // Customers.
        $value = $this->countCustomers($request);
        $data['metrics']['customers']['value'] = $value;
        $data['metrics']['customers']['change'] = $this->calcChange($value, $this->countCustomers($request, true));

        // Conversations per Day.
        $convs_by_day = $this->getConvsByDay($request);

        $value = $this->countConvsPerDay($convs_by_day);
        $data['metrics']['conv_day']['value'] = $value;
        $data['metrics']['conv_day']['change'] = $this->calcChange($value, $this->countConvsPerDay($this->getConvsByDay($request, true)));

        $data['metrics']['busy_day']['value'] = $this->countBusyDay($convs_by_day);

        // Chart.
        $data['chart']['type'] = $request->chart['type'] ?? 'new_conv';

        $data['chart']['group_bys'] = $this->getChartGroupBys($request);
        $data['chart']['group_by'] = $this->getChartGroupBy($request, $data['chart']['group_bys']);

        switch ($data['chart']['type']) {
            case 'new_conv':
                $data['chart'] = $this->chartNewConv($data['chart'], $request);
                break;

            case 'messages':
                $data['chart'] = $this->chartMessages($data['chart'], $request);
                break;
        }

        // Tables.
        $data['table_customers'] = $this->tableCustomers($request);
        $data['table_tags'] = [];
        if (\Module::isActive('tags')) {
            $data['table_tags'] = $this->tableTags($request);
        }

        return $data;
    }

    public function getReportDataProductivity($request)
    {
        $data = [];

        // Customers Helped.
        $value = $this->countCustomersHelped($request);
        $data['metrics']['customers_helped']['value'] = $value;
        $data['metrics']['customers_helped']['change'] = $this->calcChange($value, $this->countCustomersHelped($request, true));

        // Rplies Sent.
        $value = $this->countRepliesSent($request);
        $data['metrics']['replies']['value'] = $value;
        $data['metrics']['replies']['change'] = $this->calcChange($value, $this->countRepliesSent($request, true));

        // Conversations per Day.
        $value = $this->countRepliesPerDay($request);
        $data['metrics']['replies_day']['value'] = $value;
        $data['metrics']['replies_day']['change'] = $this->calcChange($value, $this->countRepliesPerDay($request, true));

        // Total Conversations.
        $value = $this->countClosed($request);
        $data['metrics']['closed']['value'] = $value;
        $data['metrics']['closed']['change'] = $this->calcChange($value, $this->countClosed($request, true));

        // Chart.
        $data['chart']['type'] = $request->chart['type'] ?? 'customers_helped';

        $data['chart']['group_bys'] = $this->getChartGroupBys($request);
        $data['chart']['group_by'] = $this->getChartGroupBy($request, $data['chart']['group_bys']);

        switch ($data['chart']['type']) {
            case 'customers_helped':
                $data['chart'] = $this->getChart(
                    $data['chart'],
                    $request,
                    $this->chartCustomersHelpedData($request),
                    $this->chartCustomersHelpedData($request, true)
                );
                break;

            case 'replies':
                $data['chart'] = $this->getChart(
                    $data['chart'],
                    $request,
                    $this->chartRepliesData($request),
                    $this->chartRepliesData($request, true)
                );
                break;

            case 'closed':
                $data['chart'] = $this->getChart(
                    $data['chart'],
                    $request,
                    $this->chartClosedData($request),
                    $this->chartClosedData($request, true)
                );
                break;
        }

        // Tables.
        $data['table_users'] = $this->tableUsers($request);

        return $data;
    }

    public function calcChange($cur_value, $prev_value)
    {
        if ($cur_value) {
            return round(($cur_value - $prev_value) * 100 / $cur_value);
        } else {
            return 0;
        }
    }

    /**
     * Number of conversations touched (received, replied to, status changed,
     * assigned, workflow activated on), excluding spam and deleted conversations.
     */
    public function countTotalConv($request, $prev = false)
    {
        //return Thread::distinct('conversation_id')->count('conversation_id');
        $query = Conversation::where('state', Conversation::STATE_PUBLISHED)
            ->where('status', '!=', Conversation::STATUS_SPAM);

        $this->applyFilter($query, $request, $prev, 'updated_at');

        return $query->count();
    }

    /**
     * Number of conversations created by customers or users, excluding spam and deleted conversations.
     */
    public function countNewConv($request, $prev = false)
    {
        $query = Conversation::where('state', Conversation::STATE_PUBLISHED)
            ->where('status', '!=', Conversation::STATUS_SPAM);

        $this->applyFilter($query, $request, $prev);

        return $query->count();
    }

    /**
     * Number of messages (emails) received from customers.
     */
    public function countMessages($request, $prev = false)
    {
        $query = Thread::where('conversations.state', Conversation::STATE_PUBLISHED)
            ->where('conversations.status', '!=', Conversation::STATUS_SPAM)
            ->rightJoin('conversations', function ($join) {
                $join->on('conversations.id', '=', 'threads.conversation_id');
            })
            ->where('threads.type', Thread::TYPE_CUSTOMER);

        $this->applyFilter($query, $request, $prev, 'threads.created_at');

        return $query->count();
    }

    /**
     * Number of customers who created or updated conversations.
     */
    public function countCustomers($request, $prev = false)
    {
        $query = Conversation::where('state', Conversation::STATE_PUBLISHED)
            ->where('status', '!=', Conversation::STATUS_SPAM)
            ->distinct('customer_id');

        $this->applyFilter($query, $request, $prev);

        return $query->count('customer_id');
    }

    public function getConvsByDay($request, $prev = false)
    {
        $query = Conversation::where('state', Conversation::STATE_PUBLISHED)
            ->where('status', '!=', Conversation::STATUS_SPAM)
            ->groupBy('updated_date');

        $this->applyFilter($query, $request, $prev);

        if (\Helper::isMySql()) {
            $data = $query->get(array(
                \DB::raw('DATE(created_at) as updated_date'),
                \DB::raw('COUNT(*) as conv_count')
            ));
        } else {
            $data = $query->get(array(
                \DB::raw("date_trunc('day', created_at) updated_date"),
                \DB::raw('COUNT(*) conv_count')
            ));
        }

        return $data;
    }

    /**
     * Average number of new or updated conversations per day.
     */
    public function countConvsPerDay($convs_by_day)
    {
        if (count($convs_by_day)) {
            return round($convs_by_day->sum('conv_count') / count($convs_by_day));
        } else {
            return 0;
        }
    }

    /**
     * Day of the week with the highest number of new conversations on average.
     */
    public function countBusyDay($convs_by_day)
    {
        $stats = [];

        foreach ($convs_by_day as $row) {
            $day = User::dateFormat($row['updated_date'], 'l');
            if (!isset($stats[$day])) {
                $stats[$day] = 0;
            }
            $stats[$day] += $row['conv_count'];
        }

        $busy_day = '-';
        $max = 0;
        foreach ($stats as $day => $conv_count) {
            if ($conv_count > $max) {
                $max = $conv_count;
                $busy_day = $day;
            }
        }
        return $busy_day;
    }

    public function prevDiffInDays($request)
    {
        $days = 0;

        $from = $request->filters['from'];
        $to = $request->filters['to'];

        if ($from && $to) {
            $from_carbon = Carbon::parse($from);
            $to_carbon = Carbon::parse($to);

            $days = $from_carbon->diffInDays($to_carbon);
        }

        return $days;
    }

    public function applyFilter($query, $request, $prev = false, $date_field = 'created_at')
    {
        $from = $request->filters['from'];
        $to = $request->filters['to'];

        if ($prev) {
            if ($from && $to) {
                $from_carbon = Carbon::parse($from);
                $to_carbon = Carbon::parse($to);

                $days = $from_carbon->diffInDays($to_carbon);

                if ($days) {
                    $from = $from_carbon->subDays($days)->format('Y-m-d');
                    $to = $to_carbon->subDays($days)->format('Y-m-d');
                }
            }
        }

        if (!empty($from)) {
            $query->where($date_field, '>=', date('Y-m-d 00:00:00', strtotime($from)));
        }
        if (!empty($to)) {
            $query->where($date_field, '<=', date('Y-m-d 23:59:59', strtotime($to)));
        }
        if (!empty($request->filters['type'])) {
            $query->where('conversations.type', $request->filters['type']);
        }
        if (!empty($request->filters['mailbox'])) {
            $query->where('conversations.mailbox_id', $request->filters['mailbox']);
        }
        if (!empty($request->filters['tag']) && \Module::isActive('tags')) {
            if (!strstr($query->toSql(), 'conversation_tag')) {
                $query->leftJoin('conversation_tag', function ($join) {
                    $join->on('conversations.id', '=', 'conversation_tag.conversation_id');
                });
            }
            $query->where('conversation_tag.tag_id', $request->filters['tag']);
        }

        // Custom fields.
        if (!empty($request->filters['custom_field']) && \Module::isActive('customfields')) {
            $custom_fields = \Reports::getCustomFieldFilters();

            if (count($custom_fields)) {
                foreach ($custom_fields as $custom_field) {
                    if (!empty($request->filters['custom_field'][$custom_field->id])) {
                        $join_alias = 'ccf'.$custom_field->id;
                        $value = $request->filters['custom_field'][$custom_field->id];

                        $query->join('conversation_custom_field as '.$join_alias, function ($join) use ($custom_field, $value, $join_alias) {
                            $join->on('conversations.id', '=', $join_alias.'.conversation_id');
                            $join->where($join_alias.'.custom_field_id', $custom_field->id);
                            if ($custom_field->type == \Modules\CustomFields\Entities\CustomField::TYPE_MULTI_LINE) {
                                $join->where($join_alias.'.value', 'like', '%'.$value.'%');
                            } else {
                                $join->where($join_alias.'.value', $value);
                            }
                        });
                    }
                }
            }
        }
    }

    public function getChartGroupBys($request)
    {
        $group_bys = [];

        $from = Carbon::parse($request->filters['from']);
        $to = Carbon::parse($request->filters['to']);

        $days = $to->diffInDays($from);

        if ($days < 365) {
            $group_bys[] = 'd';
        }
        if ($days > 14) {
            $group_bys[] = 'w';
        }
        if ($days > 60) {
            $group_bys[] = 'm';
        }

        return $group_bys;
    }

    public function getChartGroupBy($request, $group_bys)
    {
        $group_by = $request->chart['group_by'] ?? '';

        if (!in_array($group_by, $group_bys)) {
            $group_by = $group_bys[0];
        }
        return $group_by;
    }

    public function chartNewConv($chart, $request)
    {
        $categories = $this->chartCategories($chart, $request);

        $chart['categories'] = $this->chartCategories($chart, $request, true);

        $chart = $this->chartAddSeries(
            $chart,
            $this->chartNewConvData($request, $categories),
            $this->chartNewConvData($request, $categories, true)
        );

        return $chart;
    }

    public function chartMessages($chart, $request)
    {
        $categories = $this->chartCategories($chart, $request);

        $chart['categories'] = $this->chartCategories($chart, $request, true);

        $chart = $this->chartAddSeries(
            $chart,
            $this->chartMessagesData($request, $categories),
            $this->chartMessagesData($request, $categories, true)
        );

        return $chart;
    }

    public function chartCategories($chart, $request, $names = false)
    {
        $categories = [];

        $from = Carbon::parse($request->filters['from']);
        $to = Carbon::parse($request->filters['to']);

        if ($to->lessThanOrEqualTo($from)) {
            return $categories;
        }

        while ($to->greaterThanOrEqualTo($from)) {
            if ($names) {
                switch ($chart['group_by']) {
                    case 'd':
                        $category = $to->format('M j');
                        break;

                    case 'w':
                        $category = $to->format('M j');
                        break;

                    case 'm':
                        $category = $to->format('M j, Y');
                        break;
                }
            } else {
                $category = $to->format('Y-m-d');
            }
            array_unshift($categories, $category);
            switch ($chart['group_by']) {
                case 'd':
                    $to->subDay();
                    break;

                case 'w':
                    $to->subWeek();
                    break;

                case 'm':
                    $to->subMonth();
                    break;
            }

        }

        return $categories;
    }

    public function chartNewConvData($request, $dates = [], $prev = false)
    {
        $query = Conversation::where('state', Conversation::STATE_PUBLISHED)
            ->where('status', '!=', Conversation::STATUS_SPAM)
            ->groupBy('updated_date');

        $this->applyFilter($query, $request, $prev);

        if (\Helper::isMySql()) {
            $stats = $query->get(array(
                \DB::raw('DATE(updated_at) as updated_date'),
                \DB::raw('COUNT(*) as conv_count')
            ));
        } else {
            $stats = $query->get(array(
                \DB::raw("date_trunc('day', updated_at) updated_date"),
                \DB::raw('COUNT(*) conv_count')
            ));
        }

        return $this->chartDataByDays($stats, $dates, $request, $prev);
    }

    public function chartMessagesData($request, $dates = [], $prev = false)
    {
        $query = Thread::where('conversations.state', Conversation::STATE_PUBLISHED)
            ->where('conversations.status', '!=', Conversation::STATUS_SPAM)
            ->rightJoin('conversations', function ($join) {
                $join->on('conversations.id', '=', 'threads.conversation_id');
            })
            ->where('threads.type', Thread::TYPE_CUSTOMER)
            ->groupBy('updated_date');

        $this->applyFilter($query, $request, $prev, 'threads.created_at');

        if (\Helper::isMySql()) {
            $stats = $query->get(array(
                \DB::raw('DATE(conversations.updated_at) as updated_date'),
                \DB::raw('COUNT(*) as conv_count')
            ));
        } else {
            $stats = $query->get(array(
                \DB::raw("date_trunc('day', conversations.updated_at) updated_date"),
                \DB::raw('COUNT(*) conv_count')
            ));
        }

        return $this->chartDataByDays($stats, $dates, $request, $prev);
    }


    public function chartDataByDays($stats, $dates, $request, $prev)
    {
        // Modify dates.
        if ($prev) {
            $days = $this->prevDiffInDays($request);
            foreach ($dates as $i => $date) {
                $dates[$i] = date('Y-m-d', strtotime($date) - $days*24*60*60);
            }
        }

        $data = [];
        foreach ($dates as $i => $date) {
            foreach ($stats as $stat) {
                if (($i == 0 && strtotime($stat['updated_date']) <= strtotime($date))
                    || (strtotime($stat['updated_date']) <= strtotime($date) && strtotime($stat['updated_date']) > strtotime($dates[$i-1]))
                ) {
                    $data[] = (int)$stat['conv_count'];
                    continue 2;
                }
            }
            $data[] = 0;
        }

        return $data;
    }

    public function tableCustomers($request)
    {
        $query = Thread::where('conversations.state', Conversation::STATE_PUBLISHED)
            ->where('conversations.status', '!=', Conversation::STATUS_SPAM)
            ->rightJoin('conversations', function ($join) {
                $join->on('conversations.id', '=', 'threads.conversation_id');
            })
            ->where('threads.type', Thread::TYPE_CUSTOMER)
            ->groupBy('conversations.customer_id');

        $threads = $this->applyFilter($query, $request, false, 'threads.created_at');

        $stats = $query->get(array(
            \DB::raw('conversations.customer_id'),
            \DB::raw('COUNT(*) as messages_count')
        ));
        // if (\Helper::isMySql()) {
        //     $stats = $query->get(array(
        //         \DB::raw('conversations.customer_id'),
        //         \DB::raw('COUNT(*) as messages_count')
        //     ));
        // } else {
        //     $stats = $query->get(array(
        //         \DB::raw('conversations.customer_id'),
        //         \DB::raw('COUNT(*) messages_count')
        //     ));
        // }

        $table_customers = $stats->sortBy('messages_count')->reverse()->toArray();

        $table_customers = array_slice($table_customers, 0, \Reports::MAX_TABLE_ITEMS);

        $customer_ids = array_pluck($table_customers, 'customer_id');

        $customers = Customer::whereIn('id', $customer_ids)->get();

        foreach ($table_customers as $i => $table_customer) {
            foreach ($customers as $customer) {
                if ($customer->id == $table_customer['customer_id']) {
                    $table_customers[$i]['customer'] = $customer;
                    continue 2;
                }
            }
        }

        return $table_customers;
    }

    public function tableTags($request)
    {
        // Ideally created_at field is need in conversation_tag.
        $query = Conversation::where('conversations.state', Conversation::STATE_PUBLISHED)
            ->where('conversations.status', '!=', Conversation::STATUS_SPAM)
            ->leftJoin('conversation_tag', function ($join) {
                $join->on('conversations.id', '=', 'conversation_tag.conversation_id');
            })
            ->groupBy('conversation_tag.tag_id');

        $this->applyFilter($query, $request, false);

        $stats = $query->get(array(
            \DB::raw('conversation_tag.tag_id'),
            \DB::raw('COUNT(*) as conv_count')
        ));
        // if (\Helper::isMySql()) {
        //     $stats = $query->get(array(
        //         \DB::raw('conversation_tag.tag_id'),
        //         \DB::raw('COUNT(*) as conv_count')
        //     ));
        // } else {
        //     $stats = $query->get(array(
        //         \DB::raw('conversation_tag.tag_id'),
        //         \DB::raw('COUNT(*) conv_count')
        //     ));
        // }

        $table_tags = $stats->sortBy('messages_count')->reverse()->toArray();

        $table_tags = array_slice($table_tags, 0, \Reports::MAX_TABLE_ITEMS);

        $tag_ids = array_pluck($table_tags, 'tag_id');

        $tags = \Modules\Tags\Entities\Tag::whereIn('id', $tag_ids)->get();

        foreach ($table_tags as $i => $table_tag) {
            foreach ($tags as $tag) {
                if ($tag->id == $table_tag['tag_id']) {
                    $table_tags[$i]['tag'] = $tag;
                    continue 2;
                }
            }
        }

        return $table_tags;
    }

    // Difficult to group by workflow_id in thread's meta.
    /*public function tableWorkflows($request)
    {
        $query = Thread::where('threads.type', Thread::TYPE_LINEITME)
            ->whereIn('threads.action_type', [\Workflow::ACTION_TYPE_AUTOMATIC_WORKFLOW, \Workflow::ACTION_TYPE_MANUAL_WORKFLOW])
            ->rightJoin('conversations', function ($join) {
                $join->on('conversations.id', '=', 'threads.conversation_id');
            })
            ->groupBy('threads.id');

        $threads = $this->applyFilter($query, $request, false, 'threads.created_at');

        if (\Helper::isMySql()) {
            $stats = $query->get(array(
                \DB::raw('conversations.customer_id'),
                \DB::raw('COUNT(*) as messages_count')
            ));
        } else {
            $stats = $query->get(array(
                \DB::raw('conversations.customer_id'),
                \DB::raw('COUNT(*) messages_count')
            ));
        }

        $table_customers = $stats->sortBy('messages_count')->reverse()->toArray();

        $table_customers = array_slice($table_customers, 0, \Reports::MAX_TABLE_ITEMS);

        $customer_ids = array_pluck($table_customers, 'customer_id');

        $customers = Customer::whereIn('id', $customer_ids)->get();

        foreach ($table_customers as $i => $table_customer) {
            foreach ($customers as $customer) {
                if ($customer->id == $table_customer['customer_id']) {
                    $table_customers[$i]['customer'] = $customer;
                    continue 2;
                }
            }
        }

        return $table_customers;
    }*/

    /**
     * Customers helped.
     */
    public function countCustomersHelped($request, $prev = false)
    {
        $query = Thread::where('threads.type', Thread::TYPE_MESSAGE)
            ->where('threads.state', Thread::STATE_PUBLISHED)
            ->where('threads.user_id', '!=', null)
            ->rightJoin('conversations', function ($join) {
                $join->on('conversations.id', '=', 'threads.conversation_id');
            })
            ->distinct('conversations.customer_id');

        $this->applyFilter($query, $request, $prev, 'threads.created_at');

        return $query->count('conversations.customer_id');
    }

    /**
     * Replies sent.
     */
    public function countRepliesSent($request, $prev = false)
    {
        $query = Thread::where('threads.type', Thread::TYPE_MESSAGE)
            ->where('threads.state', Thread::STATE_PUBLISHED)
            ->where('threads.user_id', '!=', null)
            ->rightJoin('conversations', function ($join) {
                $join->on('conversations.id', '=', 'threads.conversation_id');
            });

        $this->applyFilter($query, $request, $prev, 'threads.created_at');

        return $query->count();
    }

    public function countRepliesPerDay($request, $prev = false)
    {
        $query = Thread::where('threads.type', Thread::TYPE_MESSAGE)
            ->where('threads.state', Thread::STATE_PUBLISHED)
            ->rightJoin('conversations', function ($join) {
                $join->on('conversations.id', '=', 'threads.conversation_id');
            })
            ->groupBy('updated_date');

        $this->applyFilter($query, $request, $prev, 'threads.created_at');

        if (\Helper::isMySql()) {
            $data = $query->get(array(
                \DB::raw('DATE(threads.created_at) as updated_date'),
                \DB::raw('COUNT(*) as thread_count')
            ));
        } else {
            $data = $query->get(array(
                \DB::raw("date_trunc('day', threads.created_at) updated_date"),
                \DB::raw('COUNT(*) thread_count')
            ));
        }

        if (count($data)) {
            return round($data->sum('thread_count') / count($data));
        } else {
            return 0;
        }
    }

    /**
     * Closed conversations.
     */
    public function countClosed($request, $prev = false)
    {
        $query = Thread::where('threads.type', Thread::TYPE_LINEITEM)
            ->where('threads.action_type', Thread::ACTION_TYPE_STATUS_CHANGED)
            ->where('threads.status', Thread::STATUS_CLOSED)
            ->where('threads.user_id', '!=', null)
            ->where('conversations.status', Conversation::STATUS_CLOSED)
            ->rightJoin('conversations', function ($join) {
                $join->on('conversations.id', '=', 'threads.conversation_id');
            })
            ->distinct('conversations.id');

        $this->applyFilter($query, $request, $prev, 'threads.created_at');

        return $query->count('conversations.id');
    }

    public function chartAddSeries($chart, $data, $data_prev)
    {
        $chart['series'][] = [
            'name' => __('Current'),
            'fillOpacity' => '0.3',
            'data' => $data
        ];
        $chart['series'][] = [
            'name' => __('Previous'),
            'lineColor' => 'rgb(137,150,163)',
            //'lineWidth' => '1',
            'fillOpacity' => '0',
            'data' => $data_prev
        ];

        return $chart;
    }

    public function getChart($chart, $request, $data, $data_prev)
    {
        $categories = $this->chartCategories($chart, $request);

        $chart['categories'] = $this->chartCategories($chart, $request, true);

        $chart = $this->chartAddSeries(
            $chart,
            $this->chartDataByDays($data, $categories, $request, false),
            $this->chartDataByDays($data_prev, $categories, $request, true)
        );

        return $chart;
    }

    public function chartCustomersHelpedData($request, $prev = false)
    {
        $query = Thread::where('threads.type', Thread::TYPE_MESSAGE)
            ->where('threads.state', Thread::STATE_PUBLISHED)
            ->where('threads.user_id', '!=', null)
            ->rightJoin('conversations', function ($join) {
                $join->on('conversations.id', '=', 'threads.conversation_id');
            })
            ->groupBy('updated_date');

        $this->applyFilter($query, $request, $prev, 'threads.created_at');

        if (\Helper::isMySql()) {
            $data = $query->get(array(
                \DB::raw('DATE(threads.created_at) as updated_date'),
                \DB::raw('COUNT(DISTINCT conversations.customer_id) as conv_count')
            ));
        } else {
            $data = $query->get(array(
                \DB::raw("date_trunc('day', threads.created_at) updated_date"),
                \DB::raw('COUNT(DISTINCT conversations.customer_id) conv_count')
            ));
        }

        return $data;
    }

    public function chartRepliesData($request, $prev = false)
    {
        $query = Thread::where('threads.type', Thread::TYPE_MESSAGE)
            ->where('threads.state', Thread::STATE_PUBLISHED)
            ->where('threads.user_id', '!=', null)
            ->rightJoin('conversations', function ($join) {
                $join->on('conversations.id', '=', 'threads.conversation_id');
            })
            ->groupBy('updated_date');

        $this->applyFilter($query, $request, $prev, 'threads.created_at');

        if (\Helper::isMySql()) {
            $data = $query->get(array(
                \DB::raw('DATE(threads.created_at) as updated_date'),
                \DB::raw('COUNT(*) as conv_count')
            ));
        } else {
            $data = $query->get(array(
                \DB::raw("date_trunc('day', threads.created_at) updated_date"),
                \DB::raw('COUNT(*) conv_count')
            ));
        }

        return $data;
    }

    public function chartClosedData($request, $prev = false)
    {
        $query = Thread::where('threads.type', Thread::TYPE_LINEITEM)
            ->where('threads.action_type', Thread::ACTION_TYPE_STATUS_CHANGED)
            ->where('threads.status', Thread::STATUS_CLOSED)
            ->where('threads.user_id', '!=', null)
            ->where('conversations.status', Conversation::STATUS_CLOSED)
            ->rightJoin('conversations', function ($join) {
                $join->on('conversations.id', '=', 'threads.conversation_id');
            })
            ->groupBy('updated_date');

        $this->applyFilter($query, $request, $prev, 'threads.created_at');

        if (\Helper::isMySql()) {
            $data = $query->get(array(
                \DB::raw('DATE(threads.created_at) as updated_date'),
                \DB::raw('COUNT(DISTINCT conversations.id) as conv_count')
            ));
        } else {
            $data = $query->get(array(
                \DB::raw("date_trunc('day', threads.created_at) updated_date"),
                \DB::raw('COUNT(DISTINCT conversations.id) conv_count')
            ));
        }

        return $data;
    }

    public function tableUsers($request)
    {
        $query = Thread::where('threads.type', Thread::TYPE_MESSAGE)
            ->where('threads.state', Thread::STATE_PUBLISHED)
            ->rightJoin('conversations', function ($join) {
                $join->on('conversations.id', '=', 'threads.conversation_id');
            })
            ->groupBy('threads.user_id');

        $threads = $this->applyFilter($query, $request, false, 'threads.created_at');

        $stats = $query->get(array(
            \DB::raw('threads.user_id'),
            \DB::raw('COUNT(*) as messages_count')
        ));
        // if (\Helper::isMySql()) {
        //     $stats = $query->get(array(
        //         \DB::raw('threads.user_id'),
        //         \DB::raw('COUNT(*) as messages_count')
        //     ));
        // } else {
        //     $stats = $query->get(array(
        //         \DB::raw('threads.user_id'),
        //         \DB::raw('COUNT(*) messages_count')
        //     ));
        // }

        $table_users = $stats->sortBy('messages_count')->reverse()->toArray();
        //$table_users = array_slice($table_users, 0, \Reports::MAX_TABLE_ITEMS);

        $user_ids = array_pluck($table_users, 'user_id');

        $users = User::whereIn('id', $user_ids)->get();

        foreach ($table_users as $i => $table_user) {
            foreach ($users as $user) {
                if ($user->id == $table_user['user_id']) {
                    $table_users[$i]['user'] = $user;
                    continue 2;
                }
            }
            unset($table_users[$i]);
        }

        // Get other users metrics.

        // Closed.
        $query = Thread::where('threads.type', Thread::TYPE_LINEITEM)
            ->where('threads.action_type', Thread::ACTION_TYPE_STATUS_CHANGED)
            ->where('threads.status', Thread::STATUS_CLOSED)
            ->where('conversations.status', Conversation::STATUS_CLOSED)
            ->whereIn('threads.user_id', $user_ids)
            ->rightJoin('conversations', function ($join) {
                $join->on('conversations.id', '=', 'threads.conversation_id');
            })
            ->groupBy('threads.user_id');
        $threads = $this->applyFilter($query, $request, false, 'threads.created_at');
        $stats = $query->get(array(
            \DB::raw('threads.user_id'),
            \DB::raw('COUNT(*) as messages_count')
        ));
        // if (\Helper::isMySql()) {
        //     $stats = $query->get(array(
        //         \DB::raw('threads.user_id'),
        //         \DB::raw('COUNT(*) as messages_count')
        //     ));
        // } else {
        //     $stats = $query->get(array(
        //         \DB::raw('threads.user_id'),
        //         \DB::raw('COUNT(*) messages_count')
        //     ));
        // }
        $table_users = $this->addToTable($table_users, $stats->toArray(), 'closed');

        // Customers helped.
        $query = Thread::where('threads.type', Thread::TYPE_MESSAGE)
            ->where('threads.state', Thread::STATE_PUBLISHED)
            ->whereIn('threads.user_id', $user_ids)
            ->rightJoin('conversations', function ($join) {
                $join->on('conversations.id', '=', 'threads.conversation_id');
            })
            ->groupBy('threads.user_id');
        $threads = $this->applyFilter($query, $request, false, 'threads.created_at');
        $stats = $query->get(array(
            \DB::raw('threads.user_id'),
            \DB::raw('COUNT(DISTINCT conversations.customer_id) as messages_count')
        ));
        // if (\Helper::isMySql()) {
        //     $stats = $query->get(array(
        //         \DB::raw('threads.user_id'),
        //         \DB::raw('COUNT(DISTINCT conversations.customer_id) as messages_count')
        //     ));
        // } else {
        //     $stats = $query->get(array(
        //         \DB::raw('threads.user_id'),
        //         \DB::raw('COUNT(DISTINCT conversations.customer_id) messages_count')
        //     ));
        // }
        $table_users = $this->addToTable($table_users, $stats->toArray(), 'customers_helped');

        return $table_users;
    }

    public function addToTable($table, $list, $col_name)
    {
        foreach ($table as $i => $table_item) {
            foreach ($list as $item) {
                if ($item['user_id'] == $table_item['user']->id) {
                    $table[$i][$col_name] = $item['messages_count'];
                    continue 2;
                }
            }
            $table[$i][$col_name] = 0;
        }

        return $table;
    }

    public function getReportSatisfaction($request)
    {
        $data = [];

        // Great.
        $great = $this->countGreat($request);
        $data['metrics']['great']['value'] = $great;
        $great_prev = $this->countGreat($request, true);
        $data['metrics']['great']['change'] = $this->calcChange($great, $great_prev);

        // Okay.
        $okay = $this->countOkay($request);
        $data['metrics']['okay']['value'] = $okay;
        $okay_prev = $this->countOkay($request, true);
        $data['metrics']['okay']['change'] = $this->calcChange($okay, $okay_prev);

        // Not Good.
        $notgood = $this->countNotgood($request);
        $data['metrics']['notgood']['value'] = $notgood;
        $notgood_prev = $this->countNotgood($request, true);
        $data['metrics']['notgood']['change'] = $this->calcChange($notgood, $notgood_prev);

        // Ratings.
        $ratings = (int)$data['metrics']['great']['value'] + (int)$data['metrics']['okay']['value'] +  (int)$data['metrics']['notgood']['value'];
        $data['metrics']['ratings']['value'] = $ratings;
        $ratings_prev = (int)$great_prev + (int)$okay_prev + (int)$notgood_prev;
        $data['metrics']['ratings']['change'] = $this->calcChange($ratings, $ratings_prev);

        // Satisfaction score.
        $satscore = 0;
        if ((int)$ratings) {
            $satscore = ceil(($great*100/$ratings) - ($notgood*100/$ratings));
        }
        $data['metrics']['satscore']['value'] = $satscore;
        $satscore_prev = 0;
        if ((int)$ratings_prev) {
            $satscore_prev = ceil(($great_prev*100/$ratings_prev) - ($notgood_prev*100/$ratings_prev));
        }
        $data['metrics']['satscore']['change'] = $this->calcChange($satscore, $satscore_prev);

        // Chart.
        if (!$ratings) {
            $ratings = 1;
        }
        $data['chart']['series'][] = [
            'data' => [
                [
                    'name'      => __('Good'),
                    'y'         => round($great*100/$ratings),
                    'selected'  => true,
                    'color'     => 'rgb(83,185,97)'
                ],
                [
                    'name'      => __('Okay'),
                    'y'         => round($okay*100/$ratings),
                    'color'     => 'rgb(147,161,175)'
                ],
                [
                    'name'      => __('Not Good'),
                    'y'         => round($notgood*100/$ratings),
                    'color'     => 'rgb(212,63,58)'
                ],
            ]
        ];

        // Tables.
        $data['table_ratings'] = $this->tableRatings($request);

        return $data;
    }

    /**
     * Ratings.
     */
    public function countRatings($request, $prev = false)
    {
        $query = Thread::where('threads.type', Thread::TYPE_MESSAGE)
            ->where('threads.rating', '>', 0)
            ->rightJoin('conversations', function ($join) {
                $join->on('conversations.id', '=', 'threads.conversation_id');
            });

        $this->applyFilter($query, $request, $prev, 'threads.created_at');

        return $query->count();
    }

    /**
     * Great.
     */
    public function countGreat($request, $prev = false)
    {
        $query = Thread::where('threads.type', Thread::TYPE_MESSAGE)
            ->where('threads.rating', \SatRatingsHelper::RATING_GREAT)
            ->rightJoin('conversations', function ($join) {
                $join->on('conversations.id', '=', 'threads.conversation_id');
            });

        $this->applyFilter($query, $request, $prev, 'threads.created_at');

        return $query->count();
    }

    /**
     * Okay.
     */
    public function countOkay($request, $prev = false)
    {
        $query = Thread::where('threads.type', Thread::TYPE_MESSAGE)
            ->where('threads.rating', \SatRatingsHelper::RATING_OKAY)
            ->rightJoin('conversations', function ($join) {
                $join->on('conversations.id', '=', 'threads.conversation_id');
            });

        $this->applyFilter($query, $request, $prev, 'threads.created_at');

        return $query->count();
    }

    /**
     * Not good.
     */
    public function countNotgood($request, $prev = false)
    {
        $query = Thread::where('threads.type', Thread::TYPE_MESSAGE)
            ->where('threads.rating', \SatRatingsHelper::RATING_BAD)
            ->rightJoin('conversations', function ($join) {
                $join->on('conversations.id', '=', 'threads.conversation_id');
            });

        $this->applyFilter($query, $request, $prev, 'threads.created_at');

        return $query->count();
    }

    /**
     * Not good.
     */
    public function countSatscore($request, $prev = false)
    {
        $query = Thread::where('threads.type', Thread::TYPE_MESSAGE)
            ->where('threads.rating', \SatRatingsHelper::RATING_BAD)
            ->rightJoin('conversations', function ($join) {
                $join->on('conversations.id', '=', 'threads.conversation_id');
            });

        $this->applyFilter($query, $request, $prev, 'threads.created_at');

        return $query->count();
    }

    public function tableRatings($request)
    {
        $query = Thread::select(['conversations.id', 'conversations.number', 'threads.user_id', 'conversations.customer_id', 'threads.created_at', 'threads.rating', 'threads.rating_comment'])
            ->where('threads.type', Thread::TYPE_MESSAGE)
            ->where('threads.rating', '>', 0)
            ->rightJoin('conversations', function ($join) {
                $join->on('conversations.id', '=', 'threads.conversation_id');
            })
            ->orderBy('threads.created_at', 'desc');

        $threads = $this->applyFilter($query, $request, false, 'threads.created_at');

        $table_ratings = $query->get()->toArray();

        $user_ids = array_pluck($table_ratings, 'user_id');
        $users = User::whereIn('id', $user_ids)->get();
        foreach ($table_ratings as $i => $table_rating) {
            foreach ($users as $user) {
                if ($user->id == $table_rating['user_id']) {
                    $table_ratings[$i]['user'] = $user;
                    continue 2;
                }
            }
            //unset($table_ratings[$i]);
        }

        $customer_ids = array_pluck($table_ratings, 'customer_id');
        $customers = Customer::whereIn('id', $customer_ids)->get();
        foreach ($table_ratings as $i => $table_rating) {
            foreach ($customers as $customer) {
                if ($customer->id == $table_rating['customer_id']) {
                    $table_ratings[$i]['customer'] = $customer;
                    continue 2;
                }
            }
            //unset($table_ratings[$i]);
        }

        return $table_ratings;
    }

    public function getReportTime($request)
    {
        $data = [];

        // Total Hours Spent.
        $value = $this->calcTotalHours($request);
        $data['metrics']['total_hours']['value']  = $this->formatHours($value);
        $data['metrics']['total_hours']['change'] = $this->calcChange($value, $this->calcTotalHours($request, true));

        // Avg. Hours Spent per Update
        $value = $this->calcAvgHours($request);
        $data['metrics']['avg_hours']['value']  = $this->formatHours($value);
        $data['metrics']['avg_hours']['change'] = $this->calcChange($value, $this->calcAvgHours($request, true));

        // Tables.
        $data['table_times'] = $this->tableTimes($request);
        $data['table_conv_times'] = $this->tableConvTimes($request);
        $data['table_customer_times'] = $this->tableCustomerTimes($request);

        // Chart.
        $data['chart'] = $this->chartTimes($data['table_times']);

        return $data;
    }

    public function formatHours($value)
    {
        return number_format($value / 3600, 1, '.', ' ');
    }

    public function formatSpentTime($value)
    {
        $dt = \Carbon\Carbon::now();
        $days = $dt->diffInDays($dt->copy()->addSeconds($value));
        $hours = $dt->diffInHours($dt->copy()->addSeconds($value)->subDays($days));
        $minutes = $dt->diffInMinutes($dt->copy()->addSeconds($value)->subDays($days)->subHours($hours));
        return \Carbon\CarbonInterval::days($days)->hours($hours)->minutes($minutes)->forHumans();

        //return number_format($value, 1, '.', ' ');
    }

    /**
     * Total Hours Spent.
     */
    public function calcTotalHours($request, $prev = false)
    {
        $query = \Modules\TimeTracking\Entities\Timelog::rightJoin('conversations', function ($join) {
                $join->on('conversations.id', '=', 'timelogs.conversation_id');
            });

        $this->applyFilter($query, $request, $prev, 'timelogs.updated_at');

        return $query->sum('time_spent');
    }

    /**
     * Avg. Hours Spent per Update.
     */
    public function calcAvgHours($request, $prev = false)
    {
        $query = \Modules\TimeTracking\Entities\Timelog::select('time_spent')
            ->rightJoin('conversations', function ($join) {
                $join->on('conversations.id', '=', 'timelogs.conversation_id');
            });

        $this->applyFilter($query, $request, $prev, 'timelogs.updated_at');

        $timelogs = $query->get();

        if (!count($timelogs)) {
            return 0;
        }

        return ($timelogs->sum('time_spent') / count($timelogs));
    }

    public function tableTimes($request)
    {
        $query = \Modules\TimeTracking\Entities\Timelog::select([
                'timelogs.*',
                \DB::raw('SUM(timelogs.time_spent) as time_spent'),
                \DB::raw('COUNT(DISTINCT timelogs.id) as time_count')
            ])
            ->rightJoin('conversations', function ($join) {
                $join->on('conversations.id', '=', 'timelogs.conversation_id');
            })
            ->groupBy('timelogs.user_id');

        $this->applyFilter($query, $request, false, 'timelogs.updated_at');

        $table_times = $query->get()->toArray();

        $user_ids = array_pluck($table_times, 'user_id');
        $users = User::whereIn('id', $user_ids)->get();
        foreach ($table_times as $i => $table_time) {

            $table_times[$i]['time_spent_h'] = round($table_time['time_spent'] / 3600, 1);
            $table_times[$i]['time_spent'] = $this->formatSpentTime($table_time['time_spent']);
            if (!(int)$table_time['time_count']) {
                $table_time['time_count'] = 1;
            }
            $table_times[$i]['time_avg'] = $this->formatSpentTime($table_time['time_spent'] / $table_time['time_count']);

            foreach ($users as $user) {
                if ($user->id == $table_time['user_id']) {
                    $table_times[$i]['user'] = $user;
                    continue 2;
                }
            }
            //unset($table_ratings[$i]);
        }

        return $table_times;
    }

    public function tableConvTimes($request)
    {
        $query = \Modules\TimeTracking\Entities\Timelog::select([
                'conversations.*',
                \DB::raw('SUM(timelogs.time_spent) as time_spent')
            ])
            ->rightJoin('conversations', function ($join) {
                $join->on('conversations.id', '=', 'timelogs.conversation_id');
            })
            //->where('time_spent', '>', 0)
            ->groupBy('timelogs.conversation_id');

        $this->applyFilter($query, $request, false, 'timelogs.updated_at');

        $table_times = $query->get()->sortBy('time_spent')->reverse()->toArray();

        $table_times = array_slice($table_times, 0, \Reports::MAX_TABLE_ITEMS);

        foreach ($table_times as $i => $table_time) {
            $table_times[$i]['time_spent'] = $this->formatSpentTime($table_time['time_spent']);
        }

        return $table_times;
    }

    public function tableCustomerTimes($request)
    {
        $query = \Modules\TimeTracking\Entities\Timelog::select([
                'conversations.customer_id',
                \DB::raw('SUM(timelogs.time_spent) as time_spent')
            ])
            ->rightJoin('conversations', function ($join) {
                $join->on('conversations.id', '=', 'timelogs.conversation_id');
            })
            ->groupBy('conversations.customer_id');

        $this->applyFilter($query, $request, false, 'timelogs.updated_at');

        $table_times = $query->get()->sortBy('time_spent')->reverse()->toArray();

        $table_times = array_slice($table_times, 0, \Reports::MAX_TABLE_ITEMS);

        $customer_ids = array_pluck($table_times, 'customer_id');
        $customers = Customer::whereIn('id', $customer_ids)->get();

        foreach ($table_times as $i => $table_time) {
            $table_times[$i]['time_spent'] = $this->formatSpentTime($table_time['time_spent']);

            foreach ($customers as $customer) {
                if ($customer->id == $table_time['customer_id']) {
                    $table_times[$i]['customer'] = $customer;
                    continue 2;
                }
            }
            unset($table_times[$i]);
        }

        return $table_times;
    }

    public function chartTimes($table_times)
    {
        $chart = [];

        $chart['y_title'] = __('Hours');

        $chart['categories'] = [];
        $data = [];

        foreach ($table_times as $table_row) {
            $chart['categories'][] = $table_row['user']->getFullName();
            $data[] = $table_row['time_spent_h'];
        }


        $chart['series'][] = [
            'showInLegend' => false,
            'data'         => $data
        ];

        return $chart;
    }
}
