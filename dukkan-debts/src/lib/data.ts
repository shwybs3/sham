import { prisma } from "@/lib/prisma";
import { computeBalance } from "@/lib/balance";

export type PublicCustomer = {
  id: string;
  name: string;
  phone: string | null;
  balance: number;
  transactionCount: number;
  lastActivity: Date;
};

export async function getPublicCustomers(): Promise<PublicCustomer[]> {
  const customers = await prisma.customer.findMany({
    include: { transactions: { select: { type: true, amount: true, createdAt: true } } },
  });

  return customers
    .map((c) => ({
      id: c.id,
      name: c.name,
      phone: c.phone,
      balance: computeBalance(c.transactions),
      transactionCount: c.transactions.length,
      lastActivity:
        c.transactions.length > 0
          ? c.transactions.reduce((latest, t) => (t.createdAt > latest ? t.createdAt : latest), c.createdAt)
          : c.createdAt,
    }))
    .sort((a, b) => b.balance - a.balance);
}

export async function getShopStats() {
  const customers = await getPublicCustomers();
  const totalOutstanding = customers.reduce((sum, c) => sum + Math.max(c.balance, 0), 0);
  const debtorsCount = customers.filter((c) => c.balance > 0.009).length;
  return { totalOutstanding, debtorsCount, customersCount: customers.length };
}
