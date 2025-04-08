<?php

// username
if (!isset($argv[1])) {
    echo 'Argument Invalid: Missing username' . PHP_EOL;
    exit(1);
} else {
    $username = $argv[1];
}

$url = "https://api.github.com/users/$username/events";

$options = [
    "http" => [
        "method" => "GET",
        "header" => "User-Agent: github-cli\r\n"
    ]
];

$context = stream_context_create($options);
$response = @file_get_contents($url, false, $context);

if ($response === false) {
    echo 'Failed to get response'.PHP_EOL;
    exit(1);
}

$data = json_decode($response, true);

if (empty($data)) {
    echo "No events found or error decoding JSON response" . PHP_EOL;
    exit(1);
}

// --------------------------  Contabiliza atividades -----------------------------------

echo "Atividades públicas de $username:\n\n";

$pushCount = [];
$pullRequestCount = [];
$starredRepos = [];
$created = [];

foreach ($data as $event) {
    $repoName = $event['repo']['name'];

    if ($event['type'] === 'PushEvent') {
        if (!isset($pushCount[$repoName])) {
            $pushCount[$repoName] = 0;
        }
        $pushCount[$repoName]++;
    }

    elseif ($event['type'] === 'PullRequestEvent') {
        $action = $event['payload']['action'];
        $prNumber = $event['payload']['number'];

        if (!isset($pullRequestCount[$repoName])) {
            $pullRequestCount[$repoName] = [];
        }

        if (!isset($pullRequestCount[$repoName][$action])) {
            $pullRequestCount[$repoName][$action] = 0;
        }
        $pullRequestCount[$repoName][$action]++;
    }

    elseif ($event['type'] === 'WatchEvent' && $event['payload']['action'] === 'started') {
        $starredRepos[] = $repoName;
    }

    elseif ($event['type'] === 'CreateEvent') {
        $refType = $event['payload']['ref_type'];
        $ref = $event['payload']['ref'] ?? null;

        if ($refType === 'repository') {
            $created[$repoName][] = $refType;
        }
        if ($refType === 'branch') {
            $created[$repoName][] = $ref;
        }
    }
}

