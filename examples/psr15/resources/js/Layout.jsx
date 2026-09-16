import { Link, usePage } from '@inertiajs/react'

export default function Layout({ children }) {
  const { props, flash } = usePage()

  return (
    <>
      <nav>
        <strong>{props.appName}</strong>
        <Link href="/">Home</Link>
        <Link href="/users">Users</Link>
        <Link href="/users/create">New user</Link>
      </nav>
      <main>
        {flash?.message && <p className="flash">{flash.message}</p>}
        {children}
      </main>
    </>
  )
}
