import { NextRequest, NextResponse } from "next/server";
import { prisma } from "@/lib/prisma";
import { computeBalance } from "@/lib/balance";

export async function GET(request: NextRequest) {
  const q = request.nextUrl.searchParams.get("q")?.trim() ?? "";

  if (q.length < 2) {
    return NextResponse.json({ results: [] });
  }

  const customers = await prisma.customer.findMany({
    where: { name: { contains: q } },
    include: { transactions: { select: { type: true, amount: true } } },
    take: 8,
    orderBy: { name: "asc" },
  });

  const results = customers.map((c) => ({
    id: c.id,
    name: c.name,
    balance: computeBalance(c.transactions),
  }));

  return NextResponse.json({ results });
}
