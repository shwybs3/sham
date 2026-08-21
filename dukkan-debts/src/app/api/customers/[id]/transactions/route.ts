import { NextRequest, NextResponse } from "next/server";
import { z } from "zod";
import { prisma } from "@/lib/prisma";
import { jsonError, requireAdmin, isErrorResponse } from "@/lib/api-helpers";

const schema = z.object({
  type: z.enum(["DEBT", "PAYMENT"]),
  amount: z.number().positive("المبلغ يجب أن يكون أكبر من صفر"),
  description: z.string().trim().max(300).optional().or(z.literal("")),
});

export async function POST(request: NextRequest, ctx: RouteContext<"/api/customers/[id]/transactions">) {
  const admin = await requireAdmin();
  if (isErrorResponse(admin)) return admin;

  const { id } = await ctx.params;
  const customer = await prisma.customer.findUnique({ where: { id } });
  if (!customer) return jsonError("الزبون غير موجود", 404);

  const body = await request.json().catch(() => null);
  const parsed = schema.safeParse(body);
  if (!parsed.success) return jsonError(parsed.error.issues[0]?.message ?? "بيانات غير صالحة");

  const { type, amount, description } = parsed.data;

  const transaction = await prisma.transaction.create({
    data: {
      customerId: id,
      type,
      amount,
      description: description || null,
    },
  });

  return NextResponse.json({ transaction }, { status: 201 });
}
