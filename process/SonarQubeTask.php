<?php


namespace process;


use Biz\SonarQube\SonarQubeClient;
use support\bootstrap\Log;
use Workerman\Crontab\Crontab;

class SonarQubeTask extends AbstractProcess
{
    public function onWorkerStart()
    {
        // 每周五下午18点推送
        new Crontab($this->getSonarExpression(), function () {
            try {
                $this->execute();
            } catch (\Exception $e) {
                Log::error("{SonarQube Push}" . $e->getMessage());
            }

        });
    }

    protected function execute()
    {
        $components = $this->getComponents();
        $httpClient = $this->getHttpClient();
        $senderFactory = $this->getDingDingNotification();
        $sendParams = [
            'msgtype' => 'markdown',
            'at' => [
                'isAtAll' => true
            ],
        ];
        foreach ($components as $componentId => $auth) {
            echo $componentId, ' start pull sonar ', PHP_EOL;
            $queryParams = [
                'component' => $componentId,
                'metricKeys' => 'bugs,new_bugs,coverage,new_coverage,test_errors,test_failures,code_smells,new_code_smells,alert_status,quality_gate_details,vulnerabilities,new_vulnerabilities,new_duplicated_lines_density',
                'additionalFields' => 'metrics',
            ];

            $response = $httpClient->get('/api/measures/component', [
                'query' => $queryParams
            ]);
            $result = $response->getBody()->getContents();
            $data = json_decode($result, true);
            if (empty($data)) {
                Log::warning("项目:{$componentId} 没有获取到信息!!!");
                continue;
            }
            $title = $auth['title'] . '代码分析结果';
            $sendParams['markdown']['title'] = $title;
            $sendParams['access_token'] = $auth['access_token'];
            $sendParams['secret'] = $auth['secret'];
            $measures = $this->meaureFormat($data['component']['measures']);
            $rows = $this->meauresMap();
            $this->bindMeaureValueByKey($rows, $measures);
            $content = $this->generateMsg($rows);
            Log::info('sonar.qube.result:' . "\n\t" . $content);
            echo $componentId, ' end pull sonar ', PHP_EOL;
            echo $componentId, ' start push dingding ', PHP_EOL;
            $senderFactory->send($content, $sendParams);
            echo $componentId, ' end push dingding ', PHP_EOL;
            $afterSendInfo = $senderFactory->getAfterSendInfo();
            $msg = $afterSendInfo['message'];
            Log::info('dingding.push.result:' . "\n\t" . $msg);
        }
    }

    protected function getHttpClient()
    {
        return new SonarQubeClient([
            'base_uri' => "http://sonar.codeages.work",
            'timeout' => 10.0,
            'connect_timeout' => 20
        ]);
    }

    protected function getSonarExpression()
    {
        return config('app.sonar_task_expression');
    }

    protected function getComponents()
    {
        return config('app.components');
    }

    protected function meaureFormat($measures)
    {
        foreach ($measures as &$measure) {
            $key = $measure['metric'];
            if ($key === 'quality_gate_details') {
                $details = json_decode($measure['value'], true);
                $measure['value'] = $details['conditions'];
            }
        }

        return $measures;
    }

    protected function meauresMap()
    {
        return [
            'alert_status' => [
                'title' => '项目质量状态',
                'value' => '',
                'children' => []
            ],
//    'quality_gate_details' => [
//        'title' => '质量阀',
//        'value' => '',
//        'children' => [
//            'new_coverage' => [
//                'title' => '最新覆盖率',
//                'value' => '',
//                'unit' => '%',
//            ],// Coverage on New Code
//            'new_duplicated_lines_density' => [
//                'title' => '新增代码重复率',
//                'value' => '',
//                'unit' => '%',
//            ]// Duplicated Lines on New Code
//        ]
//    ],
            'reliability' => [
                'title' => '可靠性',
                'value' => '',
                'children' => [
                    'bugs' => [
                        'title' => '当前BUG数',
                        'value' => '',
                        'unit' => '个',
                    ],
                    'new_bugs' => [
                        'title' => '新增BUG数',
                        'value' => '',
                        'unit' => '个',
                    ]
                ]
            ],
            'security' => [
                'title' => '安全性',
                'value' => '',
                'children' => [
                    'vulnerabilities' => [
                        'title' => '当前漏洞数',
                        'value' => '',
                        'unit' => '个',
                    ],
                    'new_vulnerabilities' => [
                        'title' => '新增漏洞数',
                        'value' => '',
                        'unit' => '个',
                    ]
                ]
            ],
            'maintainability' => [
                'title' => '可维护性',
                'value' => '',
                'children' => [
                    'code_smells' => [
                        'title' => '当前代码异味',
                        'value' => '',
                        'unit' => '个',
                    ],
                    'new_code_smells' => [
                        'title' => '新增代码异味',
                        'value' => '',
                        'unit' => '个',
                    ]
                ]
            ],
            'coverage_rate' => [
                'title' => '覆盖率',
                'value' => '',
                'children' => [
                    'coverage' => [
                        'title' => '当前覆盖率',
                        'value' => '',
                        'unit' => '%',
                    ],
                    'new_coverage' => [
                        'title' => '最新覆盖率',
                        'value' => '',
                        'unit' => '%',
                    ]
                ]
            ],
            'repetition_rate' => [
                'title' => '重复率',
                'value' => '',
                'children' => [
                    'new_duplicated_lines_density' => [
                        'title' => '新增代码重复率',
                        'value' => '',
                        'unit' => '%',
                    ]
                ]
            ]
        ];
    }

    protected function bindMeaureValueByKey(&$meaureMap, $measures)
    {
        foreach ($measures as $measure) {
            $key = $measure['metric'];
            $value = isset($measure['value']) ? $measure['value'] : (isset($measure['periods']) ? $measure['periods'] : '');
            foreach ($meaureMap as $mkey => &$item) {
                if ($key === $mkey) {
                    $item['value'] = $value;
                } elseif (isset($item['children'][$key])) {
                    $item['children'][$key]['value'] = $value;
                }
            }
        }
    }

    protected function generateMsg($meaureMap)
    {
        $string = "";
        $i = 0;
        foreach ($meaureMap as $item) {
            if ($i == 0) {
                $string .= "## {$item['title']}: <font color='red'>{$item['value']}</font>\n";
            } else {
                $string .= "#### {$i}. {$item['title']}\n";
//            $string .= "\n| 类型 | 值 |\n| :-----:| :---: |\n";
                if (!empty($item['children'])) {
                    foreach ($item['children'] as $key => $child) {
                        if ($child['value'] == '') {
                            continue;
                        }

                        if (is_string($child['value'])) {
                            $value = $child['value'];
                            if (is_numeric($value)) {
                                $value = round($value, 1);
                            }
//                        $string .= "|{$child['title']}|{$value}|\n";
                            $unit = !empty($child['unit']) ? $child['unit'] : '';
                            $string .= "- {$child['title']}: **{$value}$unit**\n";
                        } elseif (is_array($child['value']) && !empty($child['value'][0]) && isset($child['value'][0]['value'])) {
                            $value = $child['value'][0]['value'];
                            if (is_numeric($value)) {
                                $value = round($value, 1);
                            }
//                        $string .= "|{$child['title']}|{$value}|\n";
                            $unit = !empty($child['unit']) ? $child['unit'] : '';
                            $string .= "- {$child['title']}: **{$value}$unit**\n";
                        }
                    }
                }
            }
            $i++;
        }

        return $string;
    }

    protected function getDingDingNotification()
    {
        return $this->getBiz()['notification.dingding'];
    }
}