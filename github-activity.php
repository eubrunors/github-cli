<?php

if (in_array('--help', $argv)) {
    echo <<<EOL
Uso:
  php github-activity.php <username> [--type=PushEvent,CreateEvent]

Opções:
  --type=...       Filtra por tipos de eventos (ex: PushEvent,CreateEvent)
  --help           Exibe esta mensagem

EOL;
    exit(0);
}

// username
if (!isset($argv[1])) {
    echo 'Argument Invalid: Missing username';
    exit(1);
} else {
    $username = $argv[1];
}

// type event filter
$typesFilter = null;
foreach ($argv as $arg) {
    if (str_starts_with($arg, '--types=')) {
        $typesFilter = explode(',', substr($arg, strlen('--types=')));
    }
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
$matchedEventCount = 0;

foreach ($data as $event) {
    $repoName = $event['repo']['name'];

    if ($typesFilter && !in_array($event['type'], $typesFilter)) {
        continue;
    }
    $matchedEventCount++;

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

// --------------------------  Visualização atividades -----------------------------------

foreach ($created as $repoName => $refs) {
    foreach ($refs as $ref) {
        if ($ref === 'repository') {
            echo " - Created repository $repoName\n";
        } else {
            echo " - Created branch $ref in $repoName\n";
        }
    }
}

foreach ($pushCount as $repoName => $count) {
    echo " - Pushed $count commit(s) to $repoName\n";
}

foreach ($pullRequestCount as $repoName => $actions) {
    foreach ($actions as $action => $count) {
        echo " - " . ucfirst($action) . " $count pull request(s) in $repoName\n";
    }
}

foreach ($starredRepos as $repo) {
    echo " - Starred in $repo\n";
}

if ($typesFilter && $matchedEventCount === 0) {
    echo "(Nenhuma atividade encontrada para o(s) tipo(s): " . implode(', ', $typesFilter) . ")\n";
    echo "Digite --help para obter ajuda\n";
}
