# Forms and validation

Inertia forms submit in the background and receive validation errors as the `errors` prop. The usual flow is:

1. The client posts the form.
2. If validation fails, the action flashes the errors and redirects back.
3. The client renders the form page again, and `errors` contains the messages.
4. On success, the action redirects to the next page.

## Handling a submission

`InertiaFlash` stores data for the next page render. In Yii3 it uses `yiisoft/session`:

```php
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
        $data = json_decode((string) $request->getBody(), true) ?: [];

        $result = $this->validator->validate($data, [
            'name' => [new Required()],
            'email' => [new Required(), new Email()],
        ]);

        if (!$result->isValid()) {
            $this->flash->errors($result->getErrorMessagesIndexedByProperty());

            return $this->inertia->back($request);
        }

        $this->users->create($data['name'], $data['email']);
        $this->flash->flash('message', 'User created.');

        return $this->inertia->redirect('/users');
    }
}
```

`errors()` accepts messages indexed by field name, either one string or a list of strings per field. That is the
format of `getErrorMessagesIndexedByProperty()` in `yiisoft/validator`.

## Reading the submitted data

The Inertia client sends form data as JSON, unless the form contains files. Decode the body yourself, as above, or
add a body-parsing middleware such as `Yiisoft\Request\Body\RequestBodyParser` so that
`$request->getParsedBody()` works for both.

Forms with files are sent as `multipart/form-data`. Read fields with `getParsedBody()` and files with
`getUploadedFiles()`.

## Showing errors on the client

::: code-group

```jsx [React: useForm]
import { useForm } from '@inertiajs/react'

export default function Create() {
  const { data, setData, post, processing, errors } = useForm({ name: '', email: '' })

  const submit = (event) => {
    event.preventDefault()
    post('/users')
  }

  return (
    <form onSubmit={submit}>
      <input value={data.name} onChange={(e) => setData('name', e.target.value)} />
      {errors.name && <div>{errors.name}</div>}
      <input value={data.email} onChange={(e) => setData('email', e.target.value)} />
      {errors.email && <div>{errors.email}</div>}
      <button type="submit" disabled={processing}>Create</button>
    </form>
  )
}
```

```jsx [React: Form component]
import { Form } from '@inertiajs/react'

export default function Create() {
  return (
    <Form action="/users" method="post">
      {({ errors, processing }) => (
        <>
          <input type="text" name="name" />
          {errors.name && <div>{errors.name}</div>}
          <input type="email" name="email" />
          {errors.email && <div>{errors.email}</div>}
          <button type="submit" disabled={processing}>Create</button>
        </>
      )}
    </Form>
  )
}
```

```vue [Vue: useForm]
<script setup>
import { useForm } from '@inertiajs/vue3'

const form = useForm({ name: '', email: '' })
</script>

<template>
  <form @submit.prevent="form.post('/users')">
    <input v-model="form.name" />
    <div v-if="form.errors.name">{{ form.errors.name }}</div>
    <input v-model="form.email" />
    <div v-if="form.errors.email">{{ form.errors.email }}</div>
    <button type="submit" :disabled="form.processing">Create</button>
  </form>
</template>
```

:::

The input is kept after the redirect because the form state lives on the client.

## Error format

The `errors` prop is always present, and is an empty object when there are no errors. By default it contains the
first message for each field:

```json
{ "name": "Name cannot be blank.", "email": "Email is not a valid email address." }
```

To send every message as a list, enable `allErrors`:

```php
'crenspire/yii3-inertia' => [
    'allErrors' => true,
],
```

```json
{ "email": ["Email cannot be blank.", "Email is not a valid email address."] }
```

## Error bags

When a page has several forms, name the errors of each form with an error bag. On the client, set the `errorBag`
option of the visit, or the `errorBag` prop of `<Form>`:

```jsx
post('/login', { errorBag: 'login' })
```

The client sends the bag name in the `X-Inertia-Error-Bag` header, and the errors are nested under it:

```json
{ "login": { "email": "Invalid credentials." } }
```

On the server, flash errors to the default bag as usual; the adapter nests them. To flash several bags yourself,
pass the bag name:

```php
$this->flash->errors($loginErrors, 'login');
$this->flash->errors($registerErrors, 'register');
```

## Rendering errors without a redirect

To render a page with errors in the same request, attach them to the request instead of flashing them:

```php
$request = Inertia::withErrors($request, $result->getErrorMessagesIndexedByProperty());

return $this->inertia->render($request, 'Users/Create');
```
