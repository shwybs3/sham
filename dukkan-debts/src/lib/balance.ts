export type TxLike = { type: "DEBT" | "PAYMENT"; amount: number };

export function computeBalance(transactions: TxLike[]): number {
  return transactions.reduce(
    (sum, tx) => sum + (tx.type === "DEBT" ? tx.amount : -tx.amount),
    0
  );
}
