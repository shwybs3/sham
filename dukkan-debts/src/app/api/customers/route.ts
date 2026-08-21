import { NextRequest, NextResponse } from "next/server";
import { z } from "zod";
import { prisma } from "@/lib/prisma";
import { computeBalance } from "@/lib/balance";
import { jsonError, requireAdmin, isErrorResponse } from "@/lib/api-helpers";

export async function GET(request: NextRequest) {
  const q = request.nextUrl.searchParams.get("q")?.trim();

  const customers = await prisma.customer.findMany({
    where: q ? { name: { contains: q } } : undefined,
    include: { transactions: { select: { type: true, amount: true } } },
    orderBy: { createdAt: "desc" },
  });

  const result = customers.map((c) => ({
    id: c.id,
    name: c.name,
    phone: c.phone,
    notes: c.notes,
    balance: computeBalance(c.transactions),
    transactionCount: c.transactions.length,
    createdAt: c.createdAt,
  }));

  return NextResponse.json({ customers: result });
}

const createSchema = z.object({
  name: z.string().trim().min(2, "الاسم قصير جداً").max(100),
  phone: z.string().trim().max(30).optional().or(z.literal("")),
  notes: z.string().trim().max(500).optional().or(z.literal("")),
  initialDebt: z.number().nonnegative().optional(),
  initialDescription: z.string().trim().max(200).optional(),
});

export async function POST(request: NextRequest) {
  const admin = await requireAdmin();
  if (isErrorResponse(admin)) return admin;

  const body = await request.json().catch(() => null);
  const parsed = createSchema.safeParse(body);
  if (!parsed.success) {
    return jsonError(parsed.error.issues[0]?.message ?? "بيانات غير صالحة");
  }

  const { name, phone, notes, initialDebt, initialDescription } = parsed.data;

  const customer = await prisma.customer.create({
    data: {
      name,
      phone: phone || null,
      notes: notes || null,
      transactions:
        initialDebt && initialDebt > 0
          ? {
              create: [
                {
                  type: "DEBT",
                  amount: initialDebt,
                  description: initialDescription || null,
                },
              ],
            }
          : undefined,
    },
  });

  return NextResponse.json({ customer }, { status: 201 });
}
