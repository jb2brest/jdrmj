import pytest

def test_skipped_with_skip():
    """Test ignoré avec pytest.skip()"""
    pytest.skip("Test ignoré avec pytest.skip()")
    assert False
