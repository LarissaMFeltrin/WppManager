# WPP Manager - Manual de Funcionalidades

> O que cada tela faz e como usar

---

## PAINEL DE CONVERSAS (Dashboard de Atendimento)

### Visao Geral
Interface principal de atendimento, estilo WhatsApp Web, com **8 slots de conversas simultaneas**.

### Layout
```
┌─────────────────────────────────────────────────────────────────────┐
│ [Logo] Dashboard de Atendimento    Atendente: Maria (3/8)  [Acoes] │
├─────────────────────────────────────────────────────────────────────┤
│ ┌─────────┐ ┌─────────┐ ┌─────────┐ ┌─────────┐ ┌─────────┐        │
│ │ Slot 1  │ │ Slot 2  │ │ Slot 3  │ │ Slot 4  │ │ Vazio   │ ...    │
│ │ Joao    │ │ Maria   │ │ Pedro   │ │ Ana     │ │         │        │
│ │ [msgs]  │ │ [msgs]  │ │ [msgs]  │ │ [msgs]  │ │ Slot    │        │
│ │ [input] │ │ [input] │ │ [input] │ │ [input] │ │ Livre   │        │
│ └─────────┘ └─────────┘ └─────────┘ └─────────┘ └─────────┘        │
└─────────────────────────────────────────────────────────────────────┘
```

### Capacidade
| Item | Limite |
|------|--------|
| Conversas simultaneas | **8 slots** |
| Conversas por atendente | Configuravel (padrao: 5) |
| Tamanho max imagem | 140x100px na bubble |
| Tamanho max video | 160x110px na bubble |

### Indicadores Visuais

#### Cores das Bordas
| Cor | Significado |
|-----|-------------|
| Verde agua | Conversa ativa normal |
| **VERMELHO piscando** | Cliente aguardando resposta |
| Pontilhado cinza | Slot vazio/disponivel |

#### Timer de Espera
- Quando cliente envia mensagem e aguarda resposta
- Aparece badge vermelho: **"02:45"** (minutos:segundos)
- Borda do slot fica vermelha e pisca

### O que voce pode fazer

#### Enviar Mensagens
- **Texto**: Digite e pressione **Enter**
- **Quebra de linha**: **Shift + Enter**
- **Arquivo**: Clique no icone de clip (📎)
- **Colar imagem**: Ctrl+V (abre confirmacao)

#### Acoes em Mensagens (hover na bolha)
| Icone | Acao |
|-------|------|
| ↩️ | **Responder** - cita a mensagem |
| 😀 | **Reagir** - adiciona emoji |
| ✏️ | **Editar** - apenas suas mensagens |
| 🗑️ | **Excluir** - apenas suas mensagens |
| 📋 | **Copiar** texto |
| ➡️ | **Encaminhar** para outro chat |

#### Emoji Picker (Reacoes)
- 5 categorias: rostos, gestos, coracoes, objetos, animais
- ~40 emojis por categoria
- Clique no emoji para reagir

#### Encaminhar Mensagem
1. Clique no icone de encaminhar (➡️)
2. Abre modal com lista de suas conversas ativas
3. Selecione o destino
4. Mensagem e encaminhada

#### Editar Nome do Contato
1. Passe o mouse sobre o nome do cliente
2. Aparece icone de lapis (✏️)
3. Clique para abrir modal de edicao
4. Digite novo nome e salve

#### Menu do Slot (3 pontinhos)
| Opcao | Acao |
|-------|------|
| **Devolver p/ Fila** | Conversa volta para fila de espera |
| **Finalizar** | Encerra a conversa |

### Tipos de Midia Suportados
| Tipo | Visualizacao |
|------|--------------|
| Imagem | Miniatura clicavel (abre em tela cheia) |
| Video | Player embutido |
| Audio | Player de audio HTML |
| Documento | Icone + link para download |
| Sticker | Imagem pequena (70x70px) |
| Localizacao | Texto com coordenadas |

### Atualizacao Automatica
- **Mensagens**: Atualiza a cada **2 segundos**
- **Timer de espera**: Atualiza a cada **1 segundo**
- Novas mensagens: Slot pisca vermelho se voce nao esta olhando

### Visualizador de Imagens
- Clique em qualquer imagem para abrir em tela cheia
- Botoes: Download, Compartilhar, Fechar

---

## FILA DE ESPERA

### Visao Geral
Lista de conversas aguardando atendimento. Ordenadas por tempo (mais antigas primeiro).

### Layout
```
┌─────────────────────────────────────────────────────────────────┐
│ Fila de Espera                                    [Badge: 5]    │
│ Voce esta vinculado: 3/5 conversas                              │
├─────────────────────────────────────────────────────────────────┤
│ [ ] Selecionar todas                    [Finalizar Selecionadas]│
├─────────────────────────────────────────────────────────────────┤
│ ┌─────────────────────────────────────────────────────────────┐ │
│ │ [x] [Avatar] Joao Silva              5 min na fila [Pegar]  │ │
│ │              +5544999... "Ola, preciso de ajuda"             │ │
│ └─────────────────────────────────────────────────────────────┘ │
│ ┌─────────────────────────────────────────────────────────────┐ │
│ │ [ ] [Avatar] Maria Santos           12 min na fila [Pegar]  │ │
│ │              +5544888... "Bom dia!"                          │ │
│ └─────────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────────┘
```

### Indicadores Visuais

| Elemento | Cor | Significado |
|----------|-----|-------------|
| Borda esquerda | Amarelo | Aguardando na fila |
| Tempo na fila | Vermelho | Quanto tempo esperando |
| Badge header | Amarelo | Quantidade total na fila |
| Botao "Pegar" | Verde | Disponivel para pegar |
| Botao "Limite" | Cinza | Voce atingiu o limite |

### O que voce pode fazer

#### Pegar Conversa
1. Clique no botao verde **"Pegar"**
2. Confirme: "Pegar conversa com Joao?"
3. Conversa vai para seu painel
4. Seu contador aumenta (ex: 3/5 → 4/5)

**Se limite atingido:**
- Botao fica cinza "Limite"
- Tooltip: "Limite de conversas atingido"
- Finalize uma conversa para liberar slot

#### Selecao em Massa
1. Marque os checkboxes das conversas
2. Ou clique "Selecionar todas"
3. Botao "Finalizar Selecionadas" fica ativo
4. Confirme para finalizar todas de uma vez

### Atualizacao Automatica
- Lista atualiza a cada **5 segundos**
- Conversas pegadas por outros desaparecem
- Novas conversas aparecem automaticamente

### Estado Vazio
Quando nao ha conversas:
- Icone de check verde
- "Nenhuma conversa na fila de espera!"

---

## MEU CONSOLE (Minhas Conversas)

### Visao Geral
Lista de todas as suas conversas ativas. Gerenciamento centralizado.

### Layout
```
┌─────────────────────────────────────────────────────────────────┐
│ [Headset] Maria Santos                        [Online]          │
│ Conversas: 3/5                     [Fila de Espera] [Badge: 2]  │
├─────────────────────────────────────────────────────────────────┤
│ ⚠️ Alerta: 2 conversa(s) aguardando na fila                     │
├─────────────────────────────────────────────────────────────────┤
│ [ ] Selecionar todas                    [Finalizar Selecionadas]│
├─────────────────────────────────────────────────────────────────┤
│ ┌─────────────────────────────────────────────────────────────┐ │
│ │ [x] [Avatar] Joao Silva    [Em atendimento]                 │ │
│ │              "Ultima mensagem..."    [Abrir] [Devolver] [X] │ │
│ └─────────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────────┘
```

### Indicadores Visuais

| Elemento | Cor | Significado |
|----------|-----|-------------|
| Borda esquerda | Verde | Em atendimento |
| Badge status | Verde "Online" | Voce esta disponivel |
| Badge fila | Amarelo | Conversas esperando |
| Alerta | Amarelo | Aviso de fila cheia |

### O que voce pode fazer

#### Abrir Conversa
- Clique no botao azul **"Abrir Chat"**
- Vai direto para o painel com essa conversa

#### Devolver para Fila
1. Clique no botao amarelo **"Devolver"**
2. Confirme: "Devolver conversa para a fila?"
3. Conversa volta para fila de espera
4. Outro atendente pode pegar

#### Finalizar Conversa
1. Clique no botao vermelho **"Finalizar"** (X)
2. Confirme: "Finalizar esta conversa?"
3. Conversa e encerrada
4. Seu contador diminui (ex: 3/5 → 2/5)

#### Finalizacao em Massa
1. Marque checkboxes das conversas
2. Clique "Finalizar Selecionadas"
3. Confirme: "Finalizar X conversa(s)?"
4. Todas sao finalizadas de uma vez

### Estado Vazio
- "Nenhuma conversa ativa no momento"
- Link: "Pegue conversas da Fila de Espera"

---

## SUPERVISAO (Monitor em Tempo Real)

### Visao Geral
Painel para supervisores verem TODAS as conversas em atendimento de todos os atendentes.

### Layout
```
┌─────────────────────────────────────────────────────────────────┐
│ [Olho] Supervisao   Online: 3  Atendendo: 8  Na fila: 2         │
│                                    Filtro: [Todos atendentes ▼] │
├─────────────────────────────────────────────────────────────────┤
│ ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌──────────┐            │
│ │ Joao     │ │ Maria    │ │ Pedro    │ │ Ana      │            │
│ │ [msgs]   │ │ [02:30]  │ │ [msgs]   │ │ [msgs]   │            │
│ │ ──────── │ │ [msgs]   │ │ ──────── │ │ ──────── │            │
│ │ Carlos   │ │ ──────── │ │ Julia    │ │ Marcos   │            │
│ └──────────┘ │ Fernanda │ └──────────┘ └──────────┘            │
│              └──────────┘                                       │
└─────────────────────────────────────────────────────────────────┘
```

### Estatisticas no Header
| Metrica | Descricao |
|---------|-----------|
| **Online** | Atendentes conectados |
| **Atendendo** | Total de conversas em atendimento |
| **Na fila** | Conversas aguardando |

### Mini-Slots (Cards de Conversa)

#### Dimensoes
- Largura: 280px
- Altura: 340px
- Responsivo: 3-4 por linha em desktop

#### Indicadores Visuais
| Cor da Borda | Significado |
|--------------|-------------|
| Cinza normal | Conversa normal |
| **Vermelho + sombra** | Cliente aguardando resposta |
| **Pisca vermelho** | Mensagem nova chegou |

#### Timer de Espera
- Badge vermelho no canto: **"02:45"**
- Mostra quanto tempo cliente espera resposta
- Atualiza a cada 1 segundo

#### Tooltip (hover no header)
- Mostra: **"Atendente: Maria | 15 min atendendo"**
- Fundo verde escuro semi-transparente

#### Footer
- Mostra nome do atendente responsavel
- Icone de olho + "Maria Santos"

### O que voce pode fazer

#### Ver Conversa Expandida
1. Clique em qualquer mini-slot
2. Abre modal com conversa em tamanho maior
3. Mostra todas as mensagens
4. Clique fora ou no X para fechar

#### Filtrar por Atendente
1. Use o dropdown "Todos atendentes"
2. Selecione um atendente especifico
3. Grid mostra apenas conversas dele

#### Ver Imagem em Tela Cheia
- Clique em qualquer imagem
- Abre lightbox full-screen
- Botoes: Download, Fechar

### Atualizacao Automatica
| Recurso | Intervalo |
|---------|-----------|
| Grid de conversas | 2-3 segundos |
| Mensagens em cada slot | 1.5 segundos |
| Timer de espera | 1 segundo |

### Responsividade
| Tela | Colunas |
|------|---------|
| Desktop (>900px) | 3-4 mini-slots |
| Tablet (600-900px) | 2 mini-slots |
| Mobile (<600px) | 1 mini-slot |

---

## NOTIFICACOES E ALERTAS

### Badge de Fila (Global)
- Aparece no header em todas as telas
- Icone de sino com numero amarelo
- Mostra quantidade na fila de espera

### Indicador de Mensagem Nova
- **No Painel**: Slot pisca com borda vermelha
- **Na Supervisao**: Mini-slot pisca vermelho
- **Som**: Nao implementado (silencioso)

### Alertas de Limite
- Quando atinge limite de conversas
- Botao "Pegar" fica desabilitado (cinza)
- Tooltip explica o motivo

### Timer de Espera do Cliente
- **Onde aparece**: Painel e Supervisao
- **Formato**: MM:SS (minutos:segundos)
- **Cor**: Vermelho
- **Reset**: Quando atendente responde

---

## ATALHOS DE TECLADO

| Tecla | Acao | Tela |
|-------|------|------|
| **Enter** | Enviar mensagem | Painel |
| **Shift + Enter** | Quebra de linha | Painel |
| **Ctrl + V** | Colar imagem (abre confirm) | Painel |
| **Esc** | Fechar modal/dropdown | Todas |

---

## TIPOS DE ARQUIVO SUPORTADOS

### Envio
| Tipo | Extensoes |
|------|-----------|
| Imagem | jpg, png, gif, webp |
| Video | mp4, 3gp |
| Audio | mp3, ogg, m4a |
| Documento | pdf, doc, docx, xls, xlsx |

### Visualizacao
| Tipo | Como aparece |
|------|--------------|
| Imagem | Miniatura clicavel |
| Video | Player embutido |
| Audio | Player de audio |
| Documento | Icone + nome + download |
| Sticker | Imagem pequena |
| Localizacao | Texto com coordenadas |

---

## FLUXO TIPICO DE ATENDIMENTO

```
1. Cliente envia mensagem no WhatsApp
   ↓
2. Conversa aparece na FILA DE ESPERA
   (badge amarelo aumenta)
   ↓
3. Atendente clica "Pegar" na fila
   ↓
4. Conversa vai para MEU CONSOLE
   (contador aumenta: 2/5 → 3/5)
   ↓
5. Atendente abre o PAINEL DE CONVERSAS
   (slot ativa com a conversa)
   ↓
6. Atendente responde ao cliente
   (timer de espera reseta)
   ↓
7. Cliente responde
   (slot pisca vermelho, timer reinicia)
   ↓
8. Atendente finaliza conversa
   (slot libera, contador diminui)
   ↓
9. Se cliente mandar nova mensagem
   (cria nova conversa na fila)
```

---

## LIMITES DO SISTEMA

| Item | Limite |
|------|--------|
| Slots no painel | 8 simultaneos |
| Conversas por atendente | Configuravel (padrao: 5) |
| Preview de mensagem | ~80 caracteres |
| Tamanho de upload | 16MB |
| Polling de mensagens | 2 segundos |
| Polling da fila | 5 segundos |

---

## CORES DE REFERENCIA

| Cor | Hex | Uso |
|-----|-----|-----|
| Verde WhatsApp | #075e54 | Headers, bordas ativas |
| Verde claro | #d9fdd3 | Bolha de mensagem enviada |
| Branco | #fff | Bolha de mensagem recebida |
| Vermelho | #dc3545 | Alerta, cliente esperando |
| Amarelo | #ffc107 | Fila, aviso |
| Azul | #0088cc | Botoes primarios |
| Cinza | #636e72 | Texto secundario, offline |

---

*Manual atualizado em 2026-03-11*
