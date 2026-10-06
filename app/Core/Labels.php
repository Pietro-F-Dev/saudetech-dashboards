<?php
namespace App\Core;

/**
 * Rótulos em português para os valores dos ENUMs do banco e helpers de formatação
 * usados nas views.
 */
class Labels
{
    public const PERFIS = [
        'agente_sanitario'      => 'Agente Sanitário',
        'supervisora'           => 'Supervisora',
        'agente_administrativo' => 'Agente Administrativo',
        'admin'                 => 'Administrador',
    ];

    public const STATUS_VISTORIA = [
        'agendada'     => 'Agendada',
        'em_andamento' => 'Em andamento',
        'finalizada'   => 'Finalizada',
        'sincronizada' => 'Sincronizada',
        'cancelada'    => 'Cancelada',
    ];

    public const STATUS_SYNC = [
        'pendente'     => 'Pendente',
        'sincronizado' => 'Sincronizado',
        'falha'        => 'Falha',
    ];

    public const STATUS_ITEM = [
        'conforme'      => 'Conforme',
        'nao_conforme'  => 'Não conforme',
        'nao_aplicavel' => 'Não se aplica',
    ];

    public const SEVERIDADE = [
        'baixo'   => 'Baixo',
        'medio'   => 'Médio',
        'critico' => 'Crítico',
    ];

    public const STATUS_NOTIFICACAO = [
        'pendente' => 'Pendente',
        'enviada'  => 'Enviada',
        'falha'    => 'Falha',
    ];

    public const TIPO_INTERDICAO = ['parcial' => 'Parcial', 'total' => 'Total'];
    public const TIPO_AUTO       = ['padrao' => 'Padrão', 'interdicao' => 'Interdição'];

    /** Tipos de atividade sugeridos (o campo é texto livre no banco). */
    public const TIPOS_ATIVIDADE = [
        'alimenticio'      => 'Alimentício',
        'farmacia'         => 'Farmácia',
        'salao_beleza'     => 'Salão de beleza',
        'academia'         => 'Academia',
        'clinica_estetica' => 'Clínica estética',
        'mercado'          => 'Mercado',
    ];

    public static function get(array $map, ?string $key): string
    {
        if ($key === null || $key === '') {
            return '—';
        }
        return $map[$key] ?? ucfirst(str_replace('_', ' ', $key));
    }

    public static function tipoAtividade(?string $t): string
    {
        return self::get(self::TIPOS_ATIVIDADE, $t);
    }

    /** Classe CSS da "pill" de status (cores definidas no header). */
    public static function pill(string $value): string
    {
        return match ($value) {
            'agendada', 'pendente'              => 'pill pill-info',
            'em_andamento', 'medio'             => 'pill pill-warn',
            'finalizada', 'conforme'            => 'pill pill-ok',
            'sincronizada', 'sincronizado', 'enviada' => 'pill pill-done',
            'cancelada', 'nao_aplicavel'        => 'pill',
            'falha', 'critico', 'nao_conforme', 'total' => 'pill pill-bad',
            'baixo', 'parcial'                  => 'pill pill-warn',
            default                             => 'pill',
        };
    }

    public static function date(?string $v, string $fmt = 'd/m/Y'): string
    {
        return $v ? date($fmt, strtotime($v)) : '—';
    }

    public static function dateTime(?string $v): string
    {
        return self::date($v, 'd/m/Y H:i');
    }

    public static function e(?string $v): string
    {
        return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
    }
}
