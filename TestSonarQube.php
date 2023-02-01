<?php

defined('TOKEN') or define('TOKEN', '64bb55e038314d56626f1ee1258944f4d3e22200');

/**
 * curl post
 *
 * @param [type] $remote_server
 * @param [type] $post_string
 * @return void
 */
function curlPost($remote_server, $post_string)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $remote_server);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json;charset=utf-8'));
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_string);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    // 线下环境不用开启curl证书验证, 未调通情况可尝试添加该代码
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    // HTTP BASIC AUTH
    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    curl_setopt($ch, CURLOPT_USERPWD, TOKEN . ':');
    $data = curl_exec($ch);
    curl_close($ch);

    return $data;
}

/**
 * genereate url
 *
 * @param [type] $url
 * @param [type] $params
 * @return string
 */
function buildUrl($url, $params)
{
    if (!empty($params)) {
        $str = http_build_query($params);

        return $url . (false === strpos($url, '?') ? '?' : '&') . $str;
    }

    return $url;
}

/**
 * curl get
 *
 * @param [type] $url
 * @param array $params
 * @return void
 */
function curlGet($url, $params = [])
{
    $ch = curl_init();
    $url = buildUrl($url, $params);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json;charset=utf-8'));
    curl_setopt($ch, CURLOPT_TIMEOUT_MS, 5000);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS, 5000);
    // 线下环境不用开启curl证书验证, 未调通情况可尝试添加该代码
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    // HTTP BASIC AUTH
    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    curl_setopt($ch, CURLOPT_USERPWD, TOKEN . ':');
    $data = curl_exec($ch);
    curl_close($ch);

    return $data;
}

function meaureFormat($measures)
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

function bindMeaureValueByKey(&$meaureMap, $measures)
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

function generateMsg($meaureMap)
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

$url = 'http://sonar.codeages.work/api/measures/component';

$params = [
    'component' => '87572-suzhouqidi-edusoho',
    'metricKeys' => 'bugs,new_bugs,coverage,new_coverage,test_errors,test_failures,code_smells,new_code_smells,alert_status,quality_gate_details,vulnerabilities,new_vulnerabilities,new_duplicated_lines_density',
    'additionalFields' => 'metrics',
];

$response = curlGet($url, $params);

$meauresMap = [
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
        'title' => '覆盖率',
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

// TODO: 解析参考：http://sonar.codeages.work/dashboard?id=87572-suzhouqidi-edusoho
$rows = $meauresMap;
if ($response !== false) {
    file_put_contents('sonar.metrics.component.log', $response);
    $data = json_decode($response, true);
    $measures = meaureFormat($data['component']['measures']);
    bindMeaureValueByKey($rows, $measures);
    $content = generateMsg($rows);
    echo $content;
    file_put_contents('test.md', $content);
    exit;
} else {
    echo '请求失败';
}
