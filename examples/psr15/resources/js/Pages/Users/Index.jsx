import { Deferred, Head } from '@inertiajs/react'
import Layout from '../../Layout'

export default function Index({ count, users }) {
  return (
    <Layout>
      <Head title="Users" />
      <h1>Users ({count})</h1>
      <Deferred data="users" fallback={<p>Loading users…</p>}>
        <ul>
          {users?.map((user) => (
            <li key={user.email}>
              {user.name} &lt;{user.email}&gt;
            </li>
          ))}
        </ul>
      </Deferred>
    </Layout>
  )
}
