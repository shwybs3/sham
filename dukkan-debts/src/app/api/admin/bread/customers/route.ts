import { NextResponse } from "next/server";
import { prisma } from "@/lib/prisma";
import { requireAdmin, isErrorResponse } from "@/lib/api-helpers";

export async function GET() {
  const admin = await requireAdmin();
  if (isErrorResponse(admin)) return admin;

  const customers = await prisma.breadCustomer.findMany({
    include: { entries: { select: { quantity: true, entryDate: true } } },
    orderBy: { createdAt: "desc" },
  });

  const result = customers.map((c) => ({
    id: c.id,
    name: c.name,
    email: c.email,
    avatarUrl: c.avatarUrl,
    createdAt: c.createdAt,
    totalQuantity: c.entries.reduce((sum, e) => sum + e.quantity, 0),
    entryCount: c.entries.length,
  }));

  return NextResponse.json({ customers: result });
}
