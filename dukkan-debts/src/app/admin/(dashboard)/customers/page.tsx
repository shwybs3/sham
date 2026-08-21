import { getPublicCustomers } from "@/lib/data";
import { AdminCustomersTable } from "@/components/admin/AdminCustomersTable";

export const dynamic = "force-dynamic";

export default async function AdminCustomersPage() {
  const customers = await getPublicCustomers();

  return (
    <div className="mx-auto max-w-5xl">
      <div className="mb-6">
        <h1 className="text-2xl font-extrabold">الزبائن والديون</h1>
        <p className="text-sm text-muted">إدارة كاملة لكل الزبائن وسجل ديونهم</p>
      </div>

      <AdminCustomersTable
        initialCustomers={customers.map((c) => ({
          id: c.id,
          name: c.name,
          phone: c.phone,
          balance: c.balance,
          transactionCount: c.transactionCount,
        }))}
      />
    </div>
  );
}
