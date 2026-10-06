<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Labels;
use App\Repositories\UserRepository;
use PDOException;

class UserController extends Controller
{
    private UserRepository $repo;

    public function __construct()
    {
        parent::__construct();
        $this->repo = new UserRepository($this->pdo);
    }

    // ------------------------------------------------------------------ CRUD (somente admin)

    public function index(): void
    {
        $this->requireProfile('admin');
        $this->render('users/list', ['users' => $this->repo->all()]);
    }

    public function create(): void
    {
        $this->requireProfile('admin');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data  = $this->formData();
            $senha = (string)($_POST['senha'] ?? '');
            $error = $this->validate($data) ?? (strlen($senha) < 8 ? 'A senha deve ter no mínimo 8 caracteres.' : null);

            if ($error) {
                $this->render('users/form', ['error' => $error, 'user' => $data, 'isNew' => true]);
                return;
            }

            $data['senha_hash'] = password_hash($senha, PASSWORD_DEFAULT);
            $this->repo->create($data);
            $this->redirect('controller=user&action=index&msg=created');
        }

        $this->render('users/form', ['user' => ['perfil' => 'agente_sanitario', 'ativo' => true], 'isNew' => true]);
    }

    public function edit(): void
    {
        $this->requireProfile('admin');

        $id   = self::uuidOrNull($_GET['id'] ?? '');
        $user = $id ? $this->repo->find($id) : null;
        if (!$user) {
            $this->redirect('controller=user&action=index&msg=notfound');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data  = $this->formData();
            $error = $this->validate($data, $id);
            if ($id === Auth::id() && $data['perfil'] !== 'admin') {
                $error = 'Você não pode remover o seu próprio perfil de administrador.';
            }
            if ($error) {
                $this->render('users/form', ['error' => $error, 'user' => $data + ['id' => $id], 'isNew' => false]);
                return;
            }
            $this->repo->update($id, $data);
            $this->redirect('controller=user&action=index&msg=updated');
        }

        $this->render('users/form', ['user' => $user, 'isNew' => false]);
    }

    public function delete(): void
    {
        $this->requireProfile('admin');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Auth::checkCsrf()) {
            $this->redirect('controller=user&action=index');
        }

        $id = self::uuidOrNull($_POST['id'] ?? '');
        if (!$id || $id === Auth::id()) {
            $this->redirect('controller=user&action=index&msg=forbidden');
        }

        try {
            $this->repo->delete($id);
            $this->redirect('controller=user&action=index&msg=deleted');
        } catch (PDOException $e) {
            // 23503 = foreign_key_violation (usuário possui vistorias/notificações)
            if ($e->getCode() === '23503') {
                $this->redirect('controller=user&action=index&msg=user_in_use');
            }
            throw $e;
        }
    }

    // ------------------------------------------------------------------ Login / logout

    public function login(): void
    {
        if (Auth::id()) {
            $this->redirect('controller=dashboard&action=index');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email    = $this->post('email');
            $password = (string)($_POST['password'] ?? '');

            if ($email === '' || $password === '') {
                $this->render('auth/login', ['error' => 'Por favor informe o e-mail e a senha.', 'email' => $email]);
                return;
            }

            $user = $this->repo->findByEmail($email);
            if (!$user || !UserRepository::verifyPassword($password, $user['senha_hash'])) {
                $this->render('auth/login', ['error' => 'E-mail ou senha inválidos.', 'email' => $email]);
                return;
            }
            if (!$user['ativo']) {
                $this->render('auth/login', ['error' => 'Usuário inativo. Procure o administrador.', 'email' => $email]);
                return;
            }

            // Converte senhas do seed (md5 legado) para bcrypt no primeiro login
            if (UserRepository::needsRehash($user['senha_hash'])) {
                try {
                    $this->repo->updatePassword($user['id'], password_hash($password, PASSWORD_DEFAULT));
                } catch (PDOException) {
                    // Usuário do banco somente leitura (ex.: laboratório): o login segue normalmente,
                    // apenas a senha não é convertida para bcrypt.
                }
            }

            Auth::login($user);
            $this->redirect('controller=dashboard&action=index');
        }

        $this->render('auth/login');
    }

    public function logout(): void
    {
        Auth::logout();
        $this->redirect('controller=user&action=login');
    }

    // ------------------------------------------------------------------ Troca de senha

    public function changePassword(): void
    {
        $id      = self::uuidOrNull($_GET['id'] ?? '') ?? Auth::id();
        $isSelf  = $id === Auth::id();
        $isAdmin = Auth::isAdmin();

        if (!$isSelf && !$isAdmin) {
            $this->redirect('controller=dashboard&action=index&msg=forbidden');
        }

        $target = $this->repo->find($id);
        if (!$target) {
            $this->redirect('controller=user&action=index&msg=notfound');
        }

        $viewData = ['userId' => $id, 'userName' => $target['nome'], 'isSelf' => $isSelf, 'isAdmin' => $isAdmin];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $current = (string)($_POST['current_password'] ?? '');
            $new     = (string)($_POST['new_password'] ?? '');
            $confirm = (string)($_POST['confirm_password'] ?? '');
            $errors  = [];

            if (strlen($new) < 8) {
                $errors[] = 'A nova senha deve ter no mínimo 8 caracteres.';
            }
            if ($new !== $confirm) {
                $errors[] = 'Confirmação de senha não confere.';
            }
            // O próprio usuário precisa informar a senha atual (admin redefinindo a de outro, não)
            if ($isSelf) {
                $hash = $this->repo->getPasswordHash($id);
                if (!$hash || !UserRepository::verifyPassword($current, $hash)) {
                    $errors[] = 'Senha atual inválida.';
                }
            }

            if ($errors) {
                $this->render('users/change_password', $viewData + ['error' => implode(' ', $errors)]);
                return;
            }

            $this->repo->updatePassword($id, password_hash($new, PASSWORD_DEFAULT));
            $this->redirect(($isAdmin && !$isSelf ? 'controller=user&action=index' : 'controller=dashboard&action=index') . '&msg=password_changed');
        }

        $this->render('users/change_password', $viewData);
    }

    // ------------------------------------------------------------------ helpers

    private function formData(): array
    {
        $perfil = $this->post('perfil');
        return [
            'nome'      => $this->post('nome'),
            'email'     => mb_strtolower($this->post('email')),
            'matricula' => $this->post('matricula'),
            'perfil'    => array_key_exists($perfil, Labels::PERFIS) ? $perfil : 'agente_sanitario',
            'ativo'     => isset($_POST['ativo']),
        ];
    }

    private function validate(array $d, ?string $ignoreId = null): ?string
    {
        if ($d['nome'] === '' || $d['email'] === '') {
            return 'Nome e e-mail são obrigatórios.';
        }
        if (!filter_var($d['email'], FILTER_VALIDATE_EMAIL)) {
            return 'E-mail inválido.';
        }
        if ($this->repo->emailExists($d['email'], $ignoreId)) {
            return 'E-mail já cadastrado em outro usuário.';
        }
        if ($this->repo->matriculaExists($d['matricula'], $ignoreId)) {
            return 'Matrícula já cadastrada em outro usuário.';
        }
        return null;
    }
}
