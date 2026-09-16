import { Head } from '@inertiajs/react'
import Layout from '../Layout'

export default function Home({ phpVersion }) {
  return (
    <Layout>
      <Head title="Home" />
      <h1>Welcome</h1>
      <p>This page was rendered by PHP {phpVersion} through crenspire/yii3-inertia.</p>
    </Layout>
  )
}
