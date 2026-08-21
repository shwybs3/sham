import { NextRequest, NextResponse } from "next/server";
import { z } from "zod";
import { prisma } from "@/lib/prisma";
import { computeBalance } from "@/lib/balance";
import { jsonError, requireAdmin, isErrorResponse } from "@/lib/api-helpers";

export async function GET(_request: NextRequest, ctx: RouteContext<"/api/customers/[id]">) {
  const { id } = await ctx.params;
  const customer = await prisma.customer.findUnique({
    where: { id },
    include: { transactions: { orderBy: { createdAt: "desc" } } },
  });

  if (!customer) return jsonError("الزبون غير موجود", 404);

  return NextResponse.json({
    customer: {
      id: customer.id,
      name: customer.name,
      phone: customer.phone,
      notes: customer.notes,
      createdAt: customer.createdAt,
      balance: computeBalance(customer.transactions),
      transactions: customer.transactions,
    },
  });
}

const updateSchema = z.object({
  name: z.string().trim().min(2).max(100).optional(),
  phone: z.string().trim().max(30).nullable().optional(),
  notes: z.string().trim().max(500).nullable().optional(),
});

export async function PATCH(request: NextRequest, ctx: RouteContext<"/api/customers/[id]">) {
  const admin = await requireAdmin();
  if (isErrorResponse(admin)) return admin;

  const { id } = await ctx.params;
  const body = await request.json().catch(() => null);
  const parsed = updateSchema.safeParse(body);
  if (!parsed.success) return jsonError(parsed.error.issues[0]?.message ?? "بيانات غير صالحة");

  const exists = await prisma.customer.findUnique({ where: { id } });
  if (!exists) return jsonError("الزبون غير موجود", 404);

  const customer = await prisma.customer.update({
    where: { id },
    data: parsed.data,
  });

  return NextResponse.json({ customer });
}

export async function DELETE(_request: NextRequest, ctx: RouteContext<"/api/customers/[id]">) {
  const admin = await requireAdmin();
  if (isErrorResponse(admin)) return admin;

  const { id } = await ctx.params;
  const exists = await prisma.customer.findUnique({ where: { id } });
  if (!exists) return jsonError("الزبون غير موجود", 404);

  await prisma.customer.delete({ where: { id } });
  return NextResponse.json({ ok: true });
}
