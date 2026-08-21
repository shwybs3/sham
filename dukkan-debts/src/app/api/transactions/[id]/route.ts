import { NextRequest, NextResponse } from "next/server";
import { z } from "zod";
import { prisma } from "@/lib/prisma";
import { jsonError, requireAdmin, isErrorResponse } from "@/lib/api-helpers";

const updateSchema = z.object({
  type: z.enum(["DEBT", "PAYMENT"]).optional(),
  amount: z.number().positive().optional(),
  description: z.string().trim().max(300).nullable().optional(),
});

export async function PATCH(request: NextRequest, ctx: RouteContext<"/api/transactions/[id]">) {
  const admin = await requireAdmin();
  if (isErrorResponse(admin)) return admin;

  const { id } = await ctx.params;
  const exists = await prisma.transaction.findUnique({ where: { id } });
  if (!exists) return jsonError("العملية غير موجودة", 404);

  const body = await request.json().catch(() => null);
  const parsed = updateSchema.safeParse(body);
  if (!parsed.success) return jsonError(parsed.error.issues[0]?.message ?? "بيانات غير صالحة");

  const transaction = await prisma.transaction.update({
    where: { id },
    data: parsed.data,
  });

  return NextResponse.json({ transaction });
}

export async function DELETE(_request: NextRequest, ctx: RouteContext<"/api/transactions/[id]">) {
  const admin = await requireAdmin();
  if (isErrorResponse(admin)) return admin;

  const { id } = await ctx.params;
  const exists = await prisma.transaction.findUnique({ where: { id } });
  if (!exists) return jsonError("العملية غير موجودة", 404);

  await prisma.transaction.delete({ where: { id } });
  return NextResponse.json({ ok: true });
}
