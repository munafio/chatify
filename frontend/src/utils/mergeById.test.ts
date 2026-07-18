import { describe, expect, it } from 'vitest'
import { mergeById } from './mergeById'

describe('mergeById', () => {
  it('merges arrays without duplicates by id', () => {
    const first = [
      { id: 1, name: 'A' },
      { id: 2, name: 'B' },
    ]
    const second = [
      { id: 2, name: 'B updated' },
      { id: 3, name: 'C' },
    ]

    expect(mergeById(first, second)).toEqual([
      { id: 1, name: 'A' },
      { id: 2, name: 'B updated' },
      { id: 3, name: 'C' },
    ])
  })

  it('returns incoming items when existing is empty', () => {
    expect(mergeById([], [{ id: 'x', value: 1 }])).toEqual([{ id: 'x', value: 1 }])
  })
})
