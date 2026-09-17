import { Head, useForm } from '@inertiajs/react'
import Layout from '../../Layout'

export default function Create() {
  const { data, setData, post, processing, errors } = useForm({ name: '', email: '' })

  const submit = (event) => {
    event.preventDefault()
    post('/users')
  }

  return (
    <Layout>
      <Head title="New user" />
      <h1>New user</h1>
      <form onSubmit={submit} noValidate>
        <label>
          Name
          <input value={data.name} onChange={(e) => setData('name', e.target.value)} />
        </label>
        {errors.name && <div className="error">{errors.name}</div>}
        <label>
          Email
          <input type="email" value={data.email} onChange={(e) => setData('email', e.target.value)} />
        </label>
        {errors.email && <div className="error">{errors.email}</div>}
        <button type="submit" disabled={processing}>Create</button>
      </form>
    </Layout>
  )
}
