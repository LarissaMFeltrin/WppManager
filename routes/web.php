<?php

use App\Http\Controllers\Admin\ChatController;
use App\Http\Controllers\Admin\ContactController;
use App\Http\Controllers\Admin\ConversaController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\EmpresaController;
use App\Http\Controllers\Admin\LogController;
use App\Http\Controllers\Admin\MonitorController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\WhatsappAccountController;
use App\Http\Controllers\Auth\LoginController;
use Illuminate\Support\Facades\Route;

// Redirect root to admin
Route::get('/', fn() => redirect()->route('admin.dashboard'));

// Auth routes
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Admin routes (protected)
Route::prefix('admin')->middleware('auth')->name('admin.')->group(function () {

    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Users
    Route::resource('users', UserController::class)->except(['show']);

    // Empresas
    Route::resource('empresas', EmpresaController::class)->except(['show']);

    // WhatsApp Accounts
    Route::resource('whatsapp', WhatsappAccountController::class)->except(['show']);
    Route::post('whatsapp/{whatsapp}/disconnect', [WhatsappAccountController::class, 'disconnect'])->name('whatsapp.disconnect');
    Route::get('whatsapp/{whatsapp}/qrcode', [WhatsappAccountController::class, 'qrcode'])->name('whatsapp.qrcode');

    // Conversas
    Route::get('conversas', [ConversaController::class, 'index'])->name('conversas.index');
    Route::get('conversas/{conversa}', [ConversaController::class, 'show'])->name('conversas.show');
    Route::post('conversas/{conversa}/atender', [ConversaController::class, 'atender'])->name('conversas.atender');
    Route::post('conversas/{conversa}/finalizar', [ConversaController::class, 'finalizar'])->name('conversas.finalizar');
    Route::post('conversas/{conversa}/transferir', [ConversaController::class, 'transferir'])->name('conversas.transferir');

    // Chat
    Route::get('chat', [ChatController::class, 'index'])->name('chat');
    Route::post('chat/{conversa}/enviar', [ChatController::class, 'enviar'])->name('chat.enviar');
    Route::get('fila', [ChatController::class, 'fila'])->name('fila');

    // Monitor
    Route::get('monitor', [MonitorController::class, 'index'])->name('monitor');
    Route::get('supervisao', [MonitorController::class, 'supervisao'])->name('supervisao');
    Route::get('historico', [MonitorController::class, 'historico'])->name('historico');

    // Contatos
    Route::get('contatos', [ContactController::class, 'index'])->name('contatos.index');
    Route::get('contatos/{contact}/edit', [ContactController::class, 'edit'])->name('contatos.edit');
    Route::put('contatos/{contact}', [ContactController::class, 'update'])->name('contatos.update');
    Route::post('contatos/sincronizar', [ContactController::class, 'sincronizar'])->name('contatos.sincronizar');

    // Logs
    Route::get('logs', [LogController::class, 'index'])->name('logs');
    Route::get('logs/{log}', [LogController::class, 'show'])->name('logs.show');
    Route::post('logs/limpar', [LogController::class, 'limpar'])->name('logs.limpar');
});
