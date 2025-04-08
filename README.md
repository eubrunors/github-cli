# GitHub CLI

Uma ferramenta de linha de comando em PHP para visualizar as atividades públicas recentes de um usuário do GitHub.

## ✨ Funcionalidades implementadas

- Exibe eventos públicos de um usuário do GitHub
- Permite filtrar por tipo de evento (`--types`)
- Exibe instruções de uso com a flag `--help`

---

## 🚀 Como usar

### 1. Executar script

```bash
php github-activity.php <usuário>

#### Exemplo:

`php github-activity.php eubrunors`

### Filtrar por tipo de evento

php github-activity.php eubrunors types=PushEvent,CreateEvent

#### Suporte para:

```
PushEvent

PullRequestEvent

CreateEvent

WatchEvent
```

### Exibir Ajuda

`php github-activity.php --help`
