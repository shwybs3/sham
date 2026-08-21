import { notFound } from "next/navigation";
import { prisma } from "@/lib/prisma";
import { computeBalance } from "@/lib/balance";
import { AdminCustomerDetail } from "@/components/admin/AdminCustomerDetail";

export const dynamic = "force-dynamic";

export default async function AdminCustomerPage({ params }: PageProps<"/admin/customers/[id]">) {
  const { id } = await params;
  const customer = await prisma.customer.findUnique({
    where: { id },
    include: { transactions: { orderBy: { createdAt: "desc" } } },
  });

  if (!customer) notFound();

  const balance = computeBalance(customer.transactions);

  return (
    <AdminCustomerDetail
      customer={{
        id: customer.id,
        name: customer.name,
        phone: customer.phone,
        notes: customer.notes,
        balance,
        transactions: customer.transactions.map((t) => ({
          id: t.id,
          type: t.type,
          amount: t.amount,
          description: t.description,
          createdAt: t.createdAt.toISOString(),
        })),
      }}
    />
  );
}
