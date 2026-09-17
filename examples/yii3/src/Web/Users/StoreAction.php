<?php

declare(strict_types=1);

namespace App\Web\Users;

use Crenspire\Inertia\Flash\InertiaFlash;
use Crenspire\Inertia\Inertia;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Validator\Rule\Email;
use Yiisoft\Validator\Rule\Required;
use Yiisoft\Validator\ValidatorInterface;

final readonly class StoreAction
{
    public function __construct(
        private Inertia $inertia,
        private InertiaFlash $flash,
        private ValidatorInterface $validator,
        private UserRepository $users,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        // The Inertia client sends JSON unless the form contains files.
        $data = json_decode((string) $request->getBody(), true) ?: [];

        $result = $this->validator->validate($data, [
            'name' => [new Required()],
            'email' => [new Required(), new Email()],
        ]);

        if (!$result->isValid()) {
            $this->flash->errors($result->getErrorMessagesIndexedByProperty());

            return $this->inertia->back($request, '/users/create');
        }

        $this->users->add($data['name'], $data['email']);
        $this->flash->flash('message', "User {$data['name']} was created.");

        return $this->inertia->redirect('/users');
    }
}
